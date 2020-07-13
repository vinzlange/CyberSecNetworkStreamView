awk '
{
    # if this is a header line
    if (match($0, /^[0-9]/, _)) 
    {
        # print the header, but:

        # except for the first line,
        # we need to insert a newline,
        # as the preceding data lines
        # have been stripped of theirs

        # we also append a space to
        # separate header info from the
        # data that will get appended
        printf (NR == 1 ? "%s " : "\n%s "), $0
        # enforce line-buffering
        fflush()
    }
    # otherwise it is a data line
    else 
    {
        # remove the data address
        sub(/^\s+0x[0-9a-z]+:\s+/, " ");
        # remove all spaces
        gsub(" ", "");
        # print w/o newline
        printf "%s", $0 
    }
}
END
{
    # print final newline, as
    # the preceding data lines
    # have been stripped of theirs
    print ""
    # enforce line-buffering
    fflush()
}'

