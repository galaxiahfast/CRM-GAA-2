const path=require('path');
const {chromium}=require('C:/Users/MOISES INFORMATICA/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
(async()=>{
 const browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
 try {
  const page=await browser.newPage();
  const errors=[];page.on('pageerror',e=>errors.push(e.message));
  await page.goto('http://127.0.0.1:8788/');
  await page.waitForFunction(()=>Number(document.querySelector('#progress').value)>=11,{},{timeout:60000});
  const progress=await page.evaluate(()=>fetch('/api/status').then(r=>r.json()));
  console.log(JSON.stringify({doneProfiles:progress.doneProfiles,states:progress.states,pageErrors:errors}));
  if(errors.length)throw Error(errors.join('; '));
  await page.emulateMedia({media:'print'});
  await page.pdf({path:path.join(__dirname,'../tmp/pdfs/minimalista.pdf'),preferCSSPageSize:true,printBackground:true});
  console.log('PDF rendered');
 } finally {await browser.close()}
})().catch(e=>{console.error(e);process.exitCode=1});
