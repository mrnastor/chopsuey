#!/bin/bash

function displayUsage 
{
	echo "Usage: ./encode_hls.sh <source file path> <file prefix of output HLS segments> <logfile>" 
	exit
}

function write_status
{
	echo "$(date  +'%Y-%m-%d %H:%M:%S') STATUS $1" >> $2 
}

if [[ -z "$4"  ]]
then
	displayUsage
fi

inputfile=$1
output_prefix=$2
logfile=$3
hlsurl=$4

STATUS_ENCODING_HLS=1
STATUS_ENCODING_HLS_DONE=2
STATUS_ENCODING_HLS_ERROR=3
STATUS_UNK_ERROR=4

currentdir="$(pwd)/"
echo "$(date  +'%Y-%m-%d %H:%M:%S') CURRENTDIR $currentdir" >> $logfile
echo "$(date  +'%Y-%m-%d %H:%M:%S') HLS 1-PASS ENCODE START" >> $logfile
write_status $STATUS_ENCODING_HLS  $logfile

aspect=""

check_1280_720=$(/usr/local/bin/ffmpeg -i "$inputfile" 2>&1 | grep "1280x720")
if [[ -n "$check_1280_720" ]]
then
	aspect="-s 720x406"
fi

check_960x540=$(/usr/local/bin/ffmpeg -i "$inputfile" 2>&1 | grep "960x540")
if [[ -n "$check_960x540" ]]
then
	aspect="-s 720x406"
fi

echo "aspect=$aspect"

/usr/local/src/ffmpeg_2_0_1/ffmpeg-2.0.1/ffmpeg -deinterlace -vol 256 -y -i "$inputfile" -y -threads 4 -flags loop  -vcodec libx264 -g 250 -b 640k -bt 640k -maxrate  1500k $aspect -bufsize 3000k -qmin 2 -qmax 51 -subq 5 -me_method umh -cmp 2 -subcmp 2 -mbd 2 -b_strategy 1  -bidir_refine 1 -partitions parti4x4+partp8x8+partp4x4+partb8x8 -profile baseline -level 13 -async 100 -acodec aac -strict experimental -ar 44100 -ac 2 -ab 128k raw.ts && \
echo "$(date  +'%Y-%m-%d %H:%M:%S') HLS ENCODE SUCCESS" >> $logfile || error_exit "$(date  +'%Y-%m-%d %H:%M:%S') HLS ENCODE FAILED" $logfile $STATUS_ENCODING_HLS_ERROR

echo "$(date  +'%Y-%m-%d %H:%M:%S') HLS SEGMENT START: $output_prefix.ts" >> $logfile

/usr/local/src/ffmpeg_2_0_1/ffmpeg-2.0.1/ffmpeg -i "$currentdir"raw.ts -c copy -flags global_header -map 0 -f segment -segment_list raw.m3u8 -segment_list_size 0 -segment_time 10 -segment_format mpegts raw-%d.ts && echo "$(date  +'%Y-%m-%d %H:%M:%S') HLS SEGMENT SUCCESS" >> $logfile || error_exit "$(date  +'%Y-%m-%d %H:%M:%S') HLS SEGMENT FAILED" $logfile $STATUS_ENCODING_HLS_ERROR
echo "$(date  +'%Y-%m-%d %H:%M:%S') HLS SEGMENT END" >> $logfile

####READJUST SEGMENT DURATIONS
	offset=0
	rm -f adj_$output_prefix.m3u8
	cp raw.m3u8 adj_$output_prefix.m3u8


####ADJUST LAST SEGMENT DURATION
	srcvid=$(ffmpeg -i $2 2>&1 | grep "Duration" | cut -d ' ' -f 4 | sed s/,//)
	
	rm -f temp.m3u8
	cp raw.m3u8 temp.m3u8

	rawts=$(/usr/local/bin/ffmpeg -i raw.ts 2>&1 | grep "Duration" | cut -d ' ' -f 4 | sed s/,//)
	
	tsfiletotal="0"
	tsfilesizetotal="0"
	for j in {0..100}; do
		curfilename=$(echo "raw-$(($j)).ts")
		if [ -f $curfilename ]
		then
			init_vector=`printf '%032x' $j`
			echo "Getting info... input: $curfilename "
			curduration=$(/usr/local/bin/ffmpeg -i $curfilename 2>&1 | grep "Duration" | cut -d ' ' -f 4 | sed s/,// | sed s/00:00:// )
			cursize=$(perl -e 'printf "%d\n" ,(stat(shift))[7]; ' $curfilename)
			if [ -z "$curduration" ]
			then
				echo "ffmpeg failed to get duration"
			else
				tsfiletotal=$(echo "scale=9; $curduration + $tsfiletotal" | bc)
				tsfilesizetotal=$(echo "scale=9; $cursize + $tsfilesizetotal" | bc)
			fi
			echo "$curduration $tsfiletotal"
			eval sed -i 's/$curfilename//g' temp.m3u8
		else
			echo "Last ts file part found $j-1"
			break;
		fi	
	done
	
	
	sed 's/#EXTM3U//g' temp.m3u8 | sed 's/#EXT-X-VERSION:3//g' | sed  's/#EXT-X-MEDIA-SEQUENCE:0//g' | sed 's/#EXT-X-ALLOWCACHE:1//g' \
	| sed 's/#EXTINF://g' | sed 's/#EXT-X-TARGETDURATION:[0-9]\+//g' | sed 's/#EXT-X-ENDLIST//g' | sed 's/,//g' > temp.txt
	cat temp.txt
	m3u8total=$(awk '{ OFMT="%.6f"; sum += $1 } END { print sum }' temp.txt)
	lastduration=$(awk '{ OFMT="%.6f"; if( $1 > 0 ) last =$1 } END { print last }' temp.txt)
	diff=$(echo "scale=6; $m3u8total - $tsfiletotal" | bc)
	replace=$(echo "scale=6; $lastduration - $diff" | bc)
	echo "Prefix: $output_prefix"
	echo "Src: $2 $srcvid"
	echo "Raw TS: $rawts"
	echo "M3U8: $m3u8total"
	echo "Files: $tsfiletotal"
	echo "LastSegment: $lastduration"
	echo "Diff: $diff"
	echo "Replace with: $replace"
	echo -e '\n'
	
	checkneg=$(echo $replace | sed 's/[^-]//g')
	if [[   -z  "$checkneg" ]] 
	then
		eval sed -i 's/#EXTINF:$lastduration/#EXTINF:$replace/g' adj_$output_prefix.m3u8
	fi

####ENCRYPTION
	keyfilename=$(echo "$output_prefix.txt")
	echo "AES Key: $keyfilename"
	openssl rand 16 > $keyfilename
	key_as_hex=$(cat $keyfilename | hexdump -e '16/1 "%02x"')
	maxtsfiles=$(echo 100)
	for j in {0..100}; do
		curfilename=$(echo "raw-$(($j)).ts")
		if [ -f $curfilename ]
		then
			init_vector=`printf '%032x' $j`
			echo "Encrypting... input: $curfilename output: $3-$(($j)).ts"
			openssl aes-128-cbc -e -in $curfilename -out $output_prefix-$(($j)).ts -p -nosalt -iv $init_vector -K $key_as_hex		
		else
			echo "Last ts file part found $j-1"
			break;
		fi	
	done
	
####ENCRYPTED M3U8 CREATION	
	rm -f $output_prefix.m3u8
	cp adj_$output_prefix.m3u8 $output_prefix.m3u8
	insertstring=$(echo '3i#EXT-X-KEY:METHOD=AES-128,URI=\"$keyfilename\"')
	eval sed -i 's/raw-/$output_prefix-/g' $output_prefix.m3u8
	eval sed -i $insertstring $output_prefix.m3u8

	
echo "$(date  +'%Y-%m-%d %H:%M:%S') HLS ENCODE END" >> $logfile
write_status $STATUS_ENCODING_HLS_DONE  $logfile

echo "$(date  +'%Y-%m-%d %H:%M:%S') HLS_SIZE $tsfilesizetotal" >> "$logfile"