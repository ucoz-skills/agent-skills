#!/usr/bin/env python3
"""Build a standalone CSS theme. No network or site writes."""
import argparse,json,re
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
def rgb(value):
    if not re.fullmatch(r'#[0-9a-fA-F]{6}',value):raise ValueError('Expected #RRGGBB')
    return [int(value[n:n+2],16)/255 for n in (1,3,5)]
def luminance(value):
    x=[v/12.92 if v<=.04045 else ((v+.055)/1.055)**2.4 for v in rgb(value)]
    return sum(a*b for a,b in zip(x,[.2126,.7152,.0722]))
def contrast(a,b):
    a,b=sorted([luminance(a),luminance(b)])
    return (b+.05)/(a+.05)
def main():
    p=argparse.ArgumentParser(description=__doc__)
    p.add_argument('--theme',choices=['light','dark','lavender','mint','peach'],default='light')
    p.add_argument('--accent',help='Optional #RRGGBB; checked against surface and button text')
    p.add_argument('--hover',help='Optional hover #RRGGBB (otherwise matches custom accent)')
    p.add_argument('--output',type=Path,required=True)
    a=p.parse_args(); themes=json.loads((ROOT/'assets/themes.json').read_text(encoding='utf-8'));t=themes[a.theme].copy()
    for v in (a.accent,a.hover):
        if v and not re.fullmatch(r'#[0-9a-fA-F]{6}',v):p.error(f'{v}: expected #RRGGBB')
    if a.accent:
        t['accent']=a.accent;t['hover']=a.hover or a.accent
        t['on-accent']=max(['#ffffff','#111827'],key=lambda c:contrast(c,a.accent))
    elif a.hover:t['hover']=a.hover
    for fg,bg in [('text','surface'),('muted','surface'),('accent','surface'),('on-accent','accent'),('on-accent','hover'),('danger','surface'),('success','surface'),('warning','surface')]:
        ratio=contrast(t[fg],t[bg])
        if ratio<4.5:p.error(f'{fg}/{bg}: contrast {ratio:.2f} < 4.5; adjust colors (custom accent is also link text)')
    css=(ROOT/'assets/uwnd-overrides.css').read_text(encoding='utf-8')
    css+='\n/* Selected deployment palette; no HTML attribute or JavaScript required. */\n:root {\n'+'\n'.join(f'  --uwd-{k}: {v};' for k,v in t.items())+'\n}\n'
    a.output.parent.mkdir(parents=True,exist_ok=True);a.output.write_text(css,encoding='utf-8',newline='\n')
    print(f'{a.output}: {a.theme}, contrast checks passed')
if __name__=='__main__':main()
