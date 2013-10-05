#!/bin/bash

if [ -z "$4" ]
then
    echo "usage: $0 <file in> <file out> <desired width> <desired height>"
    exit 1
fi

file_in=$1
file_out=$2
desired_width=$3
desired_height=$4

#Setting
output_width=$desired_width
output_height=$desired_height

base_fname=$(basename "$file_in")

#Get current width and height of image
h=$(convert "$file_in" -format "%h" info:)
w=$(convert "$file_in" -format "%w" info:)
echo "width=[$w] height=[$h]"

cur_in_file=$file_in

echo "0:$cur_in_file"

if [ $w -gt $output_width ] 
then
	convert $cur_in_file -resize "$output_width" ./temp_w_$base_fname
	cur_in_file=temp_w_$base_fname
fi

echo "1:$cur_in_file"
h=$(convert $cur_in_file -format "%h" info:)
w=$(convert $cur_in_file -format "%w" info:)
echo "width=[$w] height=[$h]"

if [ $h -gt $output_height ] 
then
	convert $cur_in_file -resize "x$output_height" ./temp_h_$base_fname
	cur_in_file=temp_h_$base_fname
fi

echo "2:$cur_in_file"
echo "Output $output_width"x"$output_height"

#Create a black canvas
convert -size "$output_width"x"$output_height" xc:black black_$base_fname.jpg
composite -gravity center $cur_in_file black_$base_fname.jpg $file_out

#Cleanup temporary images
rm -rf ./temp_w_$base_fname
rm -rf ./temp_h_$base_fname
rm -rf ./black_$base_fname.jpg