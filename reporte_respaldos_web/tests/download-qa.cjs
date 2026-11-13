const fs=require('fs'),path=require('path'),assert=require('assert');
const {chromium}=require('C:/Users/MOISES INFORMATICA/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const snapshot=require('./report-snapshot.json');
(async()=>{
 const browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
 try{
  const page=await browser.newPage({acceptDownloads:true});const errors=[];page.on('pageerror',e=>errors.push(e.message));
  await page.route('**/api/**',route=>route.fulfill({contentType:'application/json',body:JSON.stringify({version:3,operation:'pdf-layout-test',total:5,done:5,totalProfiles:15,doneProfiles:15,states:[],servers:snapshot.map(s=>({...s,profiles:s.profiles.map(p=>({...p,profile:p.name,done:true}))}))})}));
  await page.goto('http://127.0.0.1:8788/');await page.waitForFunction(()=>!document.querySelector('#print').disabled);
  const group=page.locator('.profile-group').filter({hasText:'MANUEL JESUS OXTE CABRERA'});assert.equal(await group.count(),1);assert.equal(await group.locator('.profile-row td').first().textContent(),'Data');
  const downloaded=page.waitForEvent('download');await page.locator('#print').click();const download=await downloaded;
  assert.match(download.suggestedFilename(),/^Reporte_de_Respaldos_\d{2}-\d{2}-\d{4}_\d{6}\.pdf$/);
  const output=path.join(__dirname,'../output/pdf',download.suggestedFilename());fs.mkdirSync(path.dirname(output),{recursive:true});await download.saveAs(output);
  assert.deepEqual(errors,[]);console.log(output);console.log('PASS: local assets, browser PDF download, filename and attached-profile incidents');
 }finally{await browser.close()}
})().catch(e=>{console.error(e);process.exitCode=1});
