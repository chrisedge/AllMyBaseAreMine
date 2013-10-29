#!/usr/bin/perl -w

while (<>) {
  if (/\.mp3/i || /\.mpe?g/i || /\.jpe?g/i || /\.avi/i) {
    s/\S+\s+\S+(.*)/$1/;
    print ;
  }
}
