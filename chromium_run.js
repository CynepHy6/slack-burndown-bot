const puppeteer = require('puppeteer');

const api = 'https://devjira.skyeng.ru/secure/RapidBoard.jspa?view=reporting&chart=burndownChart&';
const rapidViewId = process.argv[2];
const sprintId = process.argv[3];
const url = api + `rapidViewId=${rapidViewId}&sprint=${sprintId}`;
const imgPath = process.argv[4] + '/chart' + parseInt(Date.now() / 1000) +
    '.png';
const token = process.argv[5];
const selectorCanvas = '#ghx-chart-view .overlay';
const selectorChart = '#ghx-chart-wrap';

if (url && token) {
  const headers = {
    'Content-Type': 'application/json',
    'Authorization': 'Basic ' + token,
  };
  (async () => {
    const browser = await puppeteer.launch(
        {defaultViewport: {width: 1000, height: 1000}});
    const page = await browser.newPage();
    await page.setExtraHTTPHeaders(headers);
    await page.goto(url);
    await page.waitFor(selectorCanvas);
    const chart = await page.$(selectorChart);
    await chart.screenshot({path: imgPath});
    await browser.close();
    console.log(imgPath);
  })();
}
