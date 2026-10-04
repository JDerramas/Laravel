const fs = require('fs');

const css = `
#main-header .left > div:first-child,
#main-header .left img,
#main-header .left svg,
img[alt="logo"],
.logo,
.timer,
[class*="timer"],
.time-counter {
  display: none !important;
  visibility: hidden !important;
  width: 0 !important;
  height: 0 !important;
  opacity: 0 !important;
  pointer-events: none !important;
}
`;

const dataUri = 'data:text/css;base64,' + Buffer.from(css).toString('base64');
const customDesign = JSON.stringify({
  custom_css_url: dataUri,
  primary_color: '#059669',
  secondary_color: '#0284c7'
});

console.log('ENCODED:');
console.log(encodeURIComponent(customDesign));
