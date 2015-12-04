#!/usr/bin/perl -w
use strict;
use warnings;
use File::Basename;

sub readfile {
    my $file_name = shift;
    open FILE, $file_name or die $!; 
    binmode FILE; 
    my $size = -s FILE;
    my $data = "";
    while ($size) {
	my $tmp;
	my $n = read FILE, $tmp, $size;
	die "read error $!" unless defined $n;
	$data .= $tmp;
	$size -= $n;
    }
    close(FILE);
    return $data;
}

sub writefile {
    my $file_name = shift;
    my $data = shift;
    open FILE, ">", $file_name or die $!; 
    syswrite FILE, $data;
    close(FILE);
}

for my $name (@ARGV) {
    next if ! -f $name;
    my $data = readfile($name);
    my $old = $data;
    $data =~ s/\bhttp:(\/\/(?:www\.)?(?:lubava\.info|bergenrabbit\.net|bergenrabbit\.no|hippy\.ru|youtube\.[a-z]+|youtu\.be|[a-z0-9]+\.wp\.com|[a-z0-9]+\.ggpht\.com|[a-z0-9]+\.[a-z0-9]+\.flickr\.com|[a-z0-9]+\.flickr\.com)\/)/https:$1/sg;
    if ($data ne $old) {
        print "changed $name\n";
#        print $data;
#        writefile($name . ".new", $data);
        writefile($name, $data);
    }
}


