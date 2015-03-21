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
    my $from="=windows-1251";
    my $to = "=utf-8";
    my $i = index $data, $from;
    next if $i < 0;
    my $old = $data;
    substr $data, $i, length($from), $to;
    if ($data ne $old) {
        print "$name\n";
        writefile("/tmp/x.txt", $data);
        system("iconv", "-f", "windows-1251", "-t", "utf-8", "-o", "/tmp/x2.txt", "/tmp/x.txt") == 0
     	    or die "Failed to executed iconv for $name, the exit code $?";
    	system("/bin/mv", "-f", "/tmp/x2.txt", $name) == 0
            or die "Failed to executed mv /tmp/x2.txt $name, thee exit code $?";


#        writefile($name . ".new", $data);
#        last;
    }
}


