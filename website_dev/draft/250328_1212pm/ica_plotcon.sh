#!/bin/bash

# To run plotcon
input_aln="$1"
output_file="$2"

# check window size
if [[ "$2" == *4 ]]; then
	winsize=4
elif [[ "$2" == *8 ]]; then
	winsize=8
else
	winsize=12
fi


# chech png or data
if [[ "$2" == *plotcondata* ]]; then
	plotcon -sequence "$input_aln" -winsize $winsize -graph data -goutfile "$output_file"
else
	plotcon -sequence "$input_aln" -winsize $winsize -graph png -goutfile "$output_file"
fi


#plotcon -sequence "$input_aln" -winsize 4 -graph png -goutfile "$output_file"
