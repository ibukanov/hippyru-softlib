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

sub process_href {
    my $text = shift;
    if ($text =~ m/^http:(\/\/(?:www\.)?(?:lubava\.info|youtube\.[a-z]+)(?:\/.*)?)$/s) {
	return $1;
    }
    return $text;
}

sub process_src {
    my $text = shift;
    $text = process_href($text);
    if ($text =~ m/^http:(\/\/(?:www\.)?(?:hippy\.ru|bergenrabbit\.net|bergenrabbit\.no)(?:\/.*)?)$/s) {
	return $1;
    }
    return $text;
}

for my $name (@ARGV) {
    next if ! -f $name;
    my $data = readfile($name);
    my $old = $data;
    $data =~ s/\bsrc="([^" ]+?)"/"src=\"" . process_src($1) . "\""/sge;	
    $data =~ s/\bsrc='([^' ]+?)'/"src='" . process_src($1) . "'"/sge;
    $data =~ s/\bhref="([^" ]+?)"/"href=\"" . process_href($1) . "\""/sge;	
    $data =~ s/\bhref='([^' ]+?)'/"href='" . process_href($1) . "'"/sge;
    if ($data ne $old) {
        print "changed $name\n";
#        print $data;
#        writefile($name . ".new", $data);
#        writefile($name, $data);
    }
}


