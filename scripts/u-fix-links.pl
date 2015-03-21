#!/usr/bin/perl
use strict;
use warnings;
use File::Basename;
use File::Find;

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

my %htaccess = ();

sub process_link {
    my $base_path = shift;
    my $text = shift;
    my $file;
    my $prefix = "";

    return "/" if $text =~ m|^http://(?:www\.)?hippy.ru$|s;

    if ($text =~ m|^http://(?:www\.)?hippy.ru/(.*)$|s) {
        $file = $1;
    } elsif ($text =~ m|^/(.*)$|s){
	$file = $1;
    } elsif ($text =~ m/^((http)|(https)|(mailto)|(javascript)|(rtsp)|(ed2k)):/s) {
        return $text;
    } else {
        $file = dirname($base_path) . "/" . "$text";
    }
    $file =~ s/\?.*$//s;
    $file =~ s/(.)\/$/$1/s;
    
    return $text if $file =~ m/^forum(\/|$)/;

    return "/" if length($file) == 0;

    return $text if (-f $file || -d $file);

    my $base = basename($file);
    return "/" . $base if $base =~ m/^(himia.htm|isno.htm)$/;

    if ($text =~ m(^Altai/)) {
        $text =~ s/\._/__/g;
        return $text;
    }

    print "$base_path  -  $text\n";
    return $text;

#    my $dir = dirname($link);
#
#
#    print "$base_path: ";
#    if ( -f ($prefix . $link . ".htm")) {
#        print "Missing .htm in $text\n";
#        my $i = index $text, $link;
#        if ($i >= 0) {
#            substr $text, $i, length($link), $link . ".htm";
#            return $text;
#        }
#        print "Cannot find $link in $text\n";
#    } elsif ( -f ($prefix . $link . ".html")) {
#        print "Missing .html in $text\n";
#        my $i = index $text, $link;
#        if ($i >= 0) {
#            substr $text, $i, length($link), $link . ".html";
#            return $text;
#        }
#        print "Cannot find $link in $text\n";
#    } elsif ($link =~ m/\.htm$/ && -f ($prefix . $link . "l")) { 
#        print "should be .html, not .htm, in $text\n";
#        my $i = index $text, $link;
#        if ($i >= 0) {
#            substr $text, $i, length($link), ($link . "l");
#            return $text;
#        }
#        print "Cannot find $link in $text\n";
#    } elsif ($link =~ m/\.htm$/ && -f ($prefix . (substr $link, 0, -4) . ".php")) { 
#        print "should be .php, not .htm, in $text\n";
#        my $i = index $text, $link;
#        if ($i >= 0) {
#            substr $text, $i + length($link) - 4, 4, ".php";
#            return $text;
#        }
#        print "Cannot find $link in $text\n";
#    } elsif ($link =~ m/\.html$/ && -f ($prefix . (substr $link, 0, -5) . ".php")) { 
#        print "should be .php, not .html, in $text\n";
#        my $i = index $text, $link;
#        if ($i >= 0) {
#            substr $text, $i + length($link) - 5, 5, ".php";
#            return $text;
#        }
#        print "Cannot find $link in $text\n";
#    } elsif ($link =~ m/\.html$/ && -f ($prefix . substr $link, 0, -1)) { 
#        print "should be .htm, not .html, in $text\n";
#        my $i = index $text, $link;
#        if ($i >= 0) {
#            substr $text, $i, length($link), (substr $link, 0, -1);
#            return $text;
#        }
#        print "Cannot find $link in $text\n";
#    } elsif ($link =~ m/page1\.html$/ && -f ($prefix . (substr $link, 0, -10) . "index.php")) { 
#        print "should be index.php, not page1.html, in $text\n";
#        my $i = index $text, $link;
#        if ($i >= 0) {
#            substr $text, $i + length($link) - 10, 10, "index.php";
#            return $text;
#        }
#        print "Cannot find $link in $text\n";
#    } elsif ($link =~ m/.htm$/s && -d ($prefix . (substr $link, 0, -4))) {
#        print "should end with /, not with .htm, in $text";
#    } elsif ($link =~ m/.html$/s && -d ($prefix . (substr $link, 0, -5))) {
#        print "should end with /, not with .html, in $text";
#    } else {
#	print "unknown link $text";
#    }
#    print "\n";    
#    return $text;
#
}

my @files = ();

sub get_file_list {
    my $name = $File::Find::name;
    return if ! -f $name;
    die "$name should start with ./" if $name !~ m|^\./(.*)$|;
    $name = $1;
    push @files, $name if $name =~ m/.*\.(html?|txt)$/ && -f $name;
};

chdir  $ENV{HOME} . "/html";
find({ wanted => \&get_file_list, no_chdir => 1 }, ".");

for my $name (@files) {
    my $data = readfile($name);
    my $old = $data;
    $data =~ s/href="([^"#]+?)"/"href=\"" . process_link($name, $1) . "\""/sge;	
    $data =~ s/href='([^'#]+?)'/"href='" . process_link($name, $1) . "'"/sge;
    $data =~ s/src="([^"#]+?)"/"src=\"" . process_link($name, $1) . "\""/sge;	
    $data =~ s/src='([^'#]+?)'/"src='" . process_link($name, $1) . "'"/sge;
    if ($data ne $old) {
        print "changed: $name\n";
        writefile($name, $data);
#        writefile($name . ".new", $data);
#        last;
    }
}


