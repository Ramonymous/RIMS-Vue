/**
 * RIMS – Stock Movement Sync (OPTIMIZED)
 * Optimasi: Error handling, validation, dan batch operations
 */

function fetchMovements() {
  const SHEET_NAME = 'RIMS';
  const API_URL = 'https://rims.r-dev.asia/api/part-movements';
  const API_TOKEN = 'YOUR_API_TOKEN_HERE'; // TODO: Replace with your actual API token
  const TZ = 'Asia/Jakarta';
  const START_ROW = 3;
  const SHEET_ID = '1paJRWJP3EbRQggqcnFIbtBrfNsV5mWsTvTNHw-_xUfE';

  // Validate API token
  if (API_TOKEN === 'YOUR_API_TOKEN_HERE') {
    throw new Error('API token belum dikonfigurasi. Silakan update API_TOKEN.');
  }

  // Get sheet reference
  const sheet = SpreadsheetApp.openById(SHEET_ID).getSheetByName(SHEET_NAME);
  if (!sheet) throw new Error(`Sheet "${SHEET_NAME}" tidak ditemukan`);

  // Batch get date values (1 API call instead of 2)
  const [[startDate, , endDate]] = sheet.getRange('B1:D1').getValues();

  // Validate both dates
  if (!(startDate instanceof Date) || !(endDate instanceof Date)) {
    throw new Error('Cell B1 dan D1 wajib format DATE');
  }

  // Build API URL
  const formatDate = d => Utilities.formatDate(d, TZ, 'yyyy-MM-dd');
  const url = `${API_URL}?start_date=${formatDate(startDate)}&end_date=${formatDate(endDate)}`;

  // Fetch data with error handling
  const response = UrlFetchApp.fetch(url, {
    method: 'get',
    headers: { 
      'Accept': 'application/json',
      'Authorization': `Bearer ${API_TOKEN}`
    },
    muteHttpExceptions: true,
  });

  // Check HTTP status
  const statusCode = response.getResponseCode();
  if (statusCode !== 200) {
    throw new Error(`API Error: HTTP ${statusCode} - ${response.getContentText()}`);
  }

  // Parse and validate response
  const json = JSON.parse(response.getContentText());
  if (!json.success || !Array.isArray(json.data) || json.data.length === 0) {
    console.log('No data available');
    return;
  }

  // Clear old data only if exists
  const lastRow = sheet.getLastRow();
  if (lastRow >= START_ROW) {
    sheet.getRange(START_ROW, 1, lastRow - START_ROW + 1, 5).clearContent();
  }

  // Map data (optimized with direct calculations)
  const rows = json.data.map(item => {
    const [y, m, d] = item.date.split('-');
    const [hh, mm, ss] = item.time.split(':');

    return [
      item.part_number,
      new Date(+y, +m - 1, +d),
      (+hh * 3600 + +mm * 60 + +ss) / 86400,
      item.type,
      +item.qty
    ];
  });

  // Batch write and format (3 operations total instead of 4)
  const range = sheet.getRange(START_ROW, 1, rows.length, 5);
  range.setValues(rows);
  
  // Apply formatting in single batch per column
  sheet.getRange(START_ROW, 2, rows.length, 1).setNumberFormat('yyyy-mm-dd');
  sheet.getRange(START_ROW, 3, rows.length, 1).setNumberFormat('hh:mm:ss');
  sheet.getRange(START_ROW, 5, rows.length, 1).setNumberFormat('#,##0');

  // Log and alert
  const msg = `✅ Sync Berhasil: ${rows.length} rows`;
  console.log(msg);

  try {
    SpreadsheetApp.getUi().alert(msg);
  } catch (e) {
    // Silent fail for background execution
  }
}