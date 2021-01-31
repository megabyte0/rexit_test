import re
from functools import reduce
#https://en.wikipedia.org/wiki/List_of_North_American_Numbering_Plan_area_codes#Summary_table
with open('us.phone.wiki.txt','rt') as fp:
    s=fp.read()
markers=re.findall(re.escape('| {{won|{{spaces|5}} }} ||')
           .replace('won','(\w+)'),s)
matcher=re.compile(
    r'\|\{\{(%s)\|(?:\[\[[-a-zA-Z0-9 ]+\|(\d{3})\]\]|(\d{3}))\}\}'%(
        '|'.join(markers)
        )
    )
x=matcher.findall(s)
x=[(i[0],i[1] or i[2]) for i in x]
assert set(str(i) for i in range(200,1000))==set(i[1] for i in x)
codes=sorted(int(code) for marker,code in x if markers.index(marker)<=2)

codes_rle=reduce(
    lambda acc,item:(
        acc[:-1]+[[acc[-1][0],item]]
        if acc[-1][1]==item-1 else
        acc+[[item,item]]
        ),
    codes[1:],
    [[codes[0],codes[0]]]
    )

assert set(codes)==set(j for beg,end in codes_rle for j in range(beg,end+1))

with open('Phone.php','wt') as fp:
    fp.write(
r'''<?php


namespace Phone;


class Phone {
    protected $areaCodesRle="%s";
}
'''%(
    ','.join(
    str(beg) if beg==end else '%d-%d'%(beg,end)
    for beg,end in codes_rle
    )
    )
        )
