
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

sub process_link {
    my $text = shift;
    return "/" . $1 if ($text =~ m|^http://(?:www\.)?hippy.ru/(.*)$|s);
    return $text;
}


my $done = 0;

sub my_find_callback {
    return if $done;

    my $name = $File::Find::name;
    return if $name !~ m/\.(txt|html|htm)$/s;
    return if ! -f $name;

    my $data = readfile($name);
    my $old = $data;
    $data =~ s/\bhref="([^" ]+?)"/"href=\"" . process_link($1) . "\""/sge;	
    $data =~ s/\bhref='([^' ]+?)'/"href='" . process_link($1) . "'"/sge;
    $data =~ s/\bsrc="([^" ]+?)"/"src=\"" . process_link($1) . "\""/sge;	
    $data =~ s/\bsrc='([^' ]+?)'/"src='" . process_link($1) . "'"/sge;
    if ($data ne $old) {
        print "changed: $name\n";
        writefile($name, $data);
#        writefile($name . ".new", $data);
#        $done = 1;
    }
}


find({ wanted => \&my_find_callback, no_chdir => 1 }, $ENV{'HOME'} . "/html");

