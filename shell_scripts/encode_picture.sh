#!/bin/bash

function displayUsage 
{
	echo "Usage: ./resize_picture.sh <source file path> <output dir> <output base name>" 
	exit
}

if [[ -z "$3"  ]]
then
	displayUsage
fi

INC_DIR=$(dirname $0)"/../settings/"
source $INC_DIR/setting.sh

input_file=$1
output_dir=$2
output_base_name=$3

echo "$(date  +'%Y-%m-%d %H:%M:%S') $0 $1 $2 $3" >> "$DEF_SCRIPTS_LOG" 2>&1

#Convert to letter box. Fix image to 4:3 aspect
#cd $DEF_WORKFOLDER_PATH
#base_fname=$(basename "$input_file")
#"$DEF_BASE_DIR"scripts/convert_canvas.sh "$input_file" canvas_"$base_fname" 800 600
#input_file=canvas_"$base_fname"

mkdir -p $output_dir >> "$DEF_SCRIPTS_LOG" 2>&1
convert $input_file -resize '600>' "$output_dir/$output_base_name"_600.jpg >> "$DEF_SCRIPTS_LOG" 2>&1 \
	&& echo "$(date  +'%Y-%m-%d %H:%M:%S') RESIZE 600 OK" >> $DEF_SCRIPTS_LOG || echo "$(date  +'%Y-%m-%d %H:%M:%S') "$output_base_name"_ERROR RESIZE 600" >> $DEF_SCRIPTS_LOG
convert $input_file -resize '316>' "$output_dir/$output_base_name"_316.jpg >> "$DEF_SCRIPTS_LOG" 2>&1 \
	&& echo "$(date  +'%Y-%m-%d %H:%M:%S') RESIZE 316 OK" >> $DEF_SCRIPTS_LOG || echo "$(date  +'%Y-%m-%d %H:%M:%S') "$output_base_name"_ERROR RESIZE 316" >> $DEF_SCRIPTS_LOG
convert $input_file -resize '256>' "$output_dir/$output_base_name"_256.jpg >> "$DEF_SCRIPTS_LOG" 2>&1 \
	&& echo "$(date  +'%Y-%m-%d %H:%M:%S') RESIZE 256 OK" >> $DEF_SCRIPTS_LOG || echo "$(date  +'%Y-%m-%d %H:%M:%S') "$output_base_name"_ERROR RESIZE 256" >> $DEF_SCRIPTS_LOG
convert $input_file -resize '200>' "$output_dir/$output_base_name"_200.jpg >> "$DEF_SCRIPTS_LOG" 2>&1 \
	&& echo "$(date  +'%Y-%m-%d %H:%M:%S') RESIZE 200 OK" >> $DEF_SCRIPTS_LOG || echo "$(date  +'%Y-%m-%d %H:%M:%S') "$output_base_name"_ERROR RESIZE 200" >> $DEF_SCRIPTS_LOG
convert $input_file -resize '178>' "$output_dir/$output_base_name"_178.jpg >> "$DEF_SCRIPTS_LOG" 2>&1 \
	&& echo "$(date  +'%Y-%m-%d %H:%M:%S') RESIZE 178 OK" >> $DEF_SCRIPTS_LOG || echo "$(date  +'%Y-%m-%d %H:%M:%S') "$output_base_name"_ERROR RESIZE 178" >> $DEF_SCRIPTS_LOG
convert $input_file -resize '80>' "$output_dir/$output_base_name"_80.jpg >> "$DEF_SCRIPTS_LOG" 2>&1 \
	&& echo "$(date  +'%Y-%m-%d %H:%M:%S') RESIZE 80 OK" >> $DEF_SCRIPTS_LOG || echo "$(date  +'%Y-%m-%d %H:%M:%S') "$output_base_name"_ERROR RESIZE 80" >> $DEF_SCRIPTS_LOG

#Remove temporary file
#rm -rf canvas_"$base_fname"