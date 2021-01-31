from functools import reduce
format_str=(lambda x,indent,n:
            '\n'.join(' '*indent+(
                ' '.join('"%s",'%j for j in i)
                )
                       for i in
(lambda x,n:reduce(
    lambda acc,item:(
        acc[:-1]+[acc[-1]+[item]]
        if sum(len(i)+4 for i in acc[-1]+[item])<=n else
        acc+[[item]]
        ),
    x,[[]]
    ))(x,n-indent)
            )
            )
