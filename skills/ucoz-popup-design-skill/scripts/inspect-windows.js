/* Read-only function for browser evaluate. Open representative windows first.
   Does not read form values, cookies, tokens, or window/app internals. */
() => {
  const roots = [...document.querySelectorAll('[id^="_uwndTop"],.uwnd-toast-measure')];
  const props=['color','background-color','background-image','font-family','font-size','line-height','padding','border-radius','border-color','box-sizing','display','overflow','width','height','mask-image'];
  const style = (el,pseudo) => {const s=getComputedStyle(el,pseudo);return Object.fromEntries(props.map(p=>[p,s.getPropertyValue(p)]));};
  return {
    url:location.origin+location.pathname,
    viewport:{width:innerWidth,height:innerHeight},
    resources:[...document.querySelectorAll('link[rel="stylesheet"],script[src]')].map(x=>x.href||x.src),
    site:{body:style(document.body),variables:Object.fromEntries([...getComputedStyle(document.documentElement)].filter(x=>x.startsWith('--')&&/font|accent|color|radius|surface/.test(x)).map(x=>[x,getComputedStyle(document.documentElement).getPropertyValue(x)]))},
    windows:roots.map(root=>({id:root.id,classes:[...root.classList],nodes:[root,...root.querySelectorAll('*')].filter(x=>x.classList.length||x.matches('input,button,a,select,textarea,label,legend')).map(x=>({tag:x.tagName,id:x.id,classes:[...x.classList],type:x.getAttribute('type'),role:x.getAttribute('role'),tabindex:x.getAttribute('tabindex'),style:style(x),before:style(x,'::before'),rect:x.getBoundingClientRect().toJSON(),scroll:{w:x.scrollWidth,h:x.scrollHeight,cw:x.clientWidth,ch:x.clientHeight}}))}))
  };
}
