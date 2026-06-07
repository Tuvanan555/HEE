// apps-script/Code.gs
/**
 * NannKub - Google Apps Script Backend
 * 
 * Setup Instructions:
 * 1. Go to script.google.com and create a new project.
 * 2. Paste this entire file into Code.gs.
 * 3. Deploy > New Deployment > Type: Web app.
 *    - Execute as: Me
 *    - Who has access: Anyone
 * 4. Copy the Web App URL and paste it into js/api.js as SCRIPT_URL.
 * 5. Run the setup() function manually once to create folders and sheets.
 */

const FOLDER_NAME = "NannKub_Storage";
const SHEET_NAME = "NannKub_Data";
const CATEGORIES = ["โน่", "นัน", "คู่", "อื่นๆ"];

function setup() {
  createDriveFolders();
  createSheetHeaders();
}

function doGet(e) {
  // Can be used for simple health check
  return ContentService.createTextOutput(JSON.stringify({ status: "ok", message: "Love Memory API is running" }))
    .setMimeType(ContentService.MimeType.JSON);
}

function doPost(e) {
  try {
    let payload;
    if (e.postData && e.postData.contents) {
      payload = JSON.parse(e.postData.contents);
    } else {
      return responseError("No payload");
    }

    const action = payload.action;

    switch (action) {
      case "uploadImage":
        return uploadImage(payload);
      case "saveDiary":
        return saveDiary(payload);
      case "getData":
        return getData(payload);
      case "sendChat":
        return sendChat(payload);
      case "getChats":
        return getChats(payload);
      case "searchData":
        return searchData(payload);
      case "backupData":
        return backupData();
      default:
        return responseError("Unknown action");
    }
  } catch (error) {
    return responseError(error.toString());
  }
}

function responseSuccess(data) {
  return ContentService.createTextOutput(JSON.stringify({ status: "success", data: data }))
    .setMimeType(ContentService.MimeType.JSON);
}

function responseError(message) {
  return ContentService.createTextOutput(JSON.stringify({ status: "error", message: message }))
    .setMimeType(ContentService.MimeType.JSON);
}

function getDatabaseSheet() {
  const files = DriveApp.getFilesByName(SHEET_NAME);
  let sheetFile;
  if (files.hasNext()) {
    sheetFile = SpreadsheetApp.open(files.next());
  } else {
    // Create new spreadsheet
    sheetFile = SpreadsheetApp.create(SHEET_NAME);
    const rootFolder = getRootFolder();
    DriveApp.getFileById(sheetFile.getId()).moveTo(rootFolder);
  }
  return sheetFile;
}

function createSheetHeaders() {
  const ss = getDatabaseSheet();
  const headers = [
    "ID", "Timestamp", "User", "Type", "Folder", "Title", "Description", 
    "Mood", "ImageURL", "DriveFileID", "Frame", "Filter", "CreatedDate"
  ];
  
  let sheet = ss.getSheetByName("Data");
  if (!sheet) {
    sheet = ss.insertSheet("Data");
    ss.deleteSheet(ss.getSheets()[0]); // Delete default Sheet1
  }
  
  // Only set headers if empty
  if (sheet.getLastRow() === 0) {
    sheet.appendRow(headers);
    sheet.getRange(1, 1, 1, headers.length).setFontWeight("bold");
    sheet.setFrozenRows(1);
  }

  // Create Chat sheet
  let chatSheet = ss.getSheetByName("Chat");
  if (!chatSheet) {
    chatSheet = ss.insertSheet("Chat");
    chatSheet.appendRow(["ID", "User", "Text", "Timestamp"]);
    chatSheet.getRange(1, 1, 1, 4).setFontWeight("bold");
    chatSheet.setFrozenRows(1);
  }
}

function getRootFolder() {
  const folders = DriveApp.getFoldersByName(FOLDER_NAME);
  if (folders.hasNext()) {
    return folders.next();
  }
  return DriveApp.createFolder(FOLDER_NAME);
}

function createDriveFolders() {
  const root = getRootFolder();
  CATEGORIES.forEach(cat => {
    const folders = root.getFoldersByName(cat);
    if (!folders.hasNext()) {
      root.createFolder(cat);
    }
  });
}

function uploadImage(payload) {
  const { user, folder, fileName, mimeType, fileData, caption, filter, frame } = payload;
  
  // Find correct folder
  const root = getRootFolder();
  let targetFolder = root;
  const subFolders = root.getFoldersByName(folder);
  if (subFolders.hasNext()) {
    targetFolder = subFolders.next();
  }

  // Decode base64 and save
  const blob = Utilities.newBlob(Utilities.base64Decode(fileData), mimeType, fileName);
  const file = targetFolder.createFile(blob);
  
  // Set file to anyone with link can view (so UI can display it)
  file.setSharing(DriveApp.Access.ANYONE_WITH_LINK, DriveApp.Permission.VIEW);

  // Use thumbnail endpoint to bypass Google Drive's strict CORB policies on img tags
  const fileUrl = "https://drive.google.com/thumbnail?id=" + file.getId() + "&sz=w1000";
  const id = new Date().getTime().toString();

  // Save to Sheet
  const sheet = getDatabaseSheet().getSheetByName("Data");
  sheet.appendRow([
    id, new Date(), user, "Image", folder, fileName, caption || "", "", fileUrl, file.getId(), frame || "none", filter || "none", new Date()
  ]);

  return responseSuccess({ id: id, url: fileUrl });
}

function saveDiary(payload) {
  const { id, title, content, mood, user, date } = payload;
  const sheet = getDatabaseSheet().getSheetByName("Data");
  
  sheet.appendRow([
    id, new Date(), user, "Diary", "Diary", title, content, mood, "", "", "", "", date
  ]);

  return responseSuccess({ id: id });
}

function getData(payload) {
  const sheet = getDatabaseSheet().getSheetByName("Data");
  const data = sheet.getDataRange().getValues();
  
  if (data.length <= 1) return responseSuccess({ photos: [], diaries: [] });

  const headers = data[0];
  const rows = data.slice(1);
  
  const formattedData = rows.map(row => {
    let obj = {};
    headers.forEach((header, i) => {
      obj[header] = row[i];
    });
    return obj;
  });

  const photos = formattedData.filter(item => item.Type === "Image");
  const diaries = formattedData.filter(item => item.Type === "Diary");

  return responseSuccess({ photos, diaries });
}

function searchData(payload) {
  const { query } = payload;
  // Implement basic text search
  const sheet = getDatabaseSheet().getSheetByName("Data");
  const data = sheet.getDataRange().getValues();
  if (data.length <= 1) return responseSuccess([]);
  
  const headers = data[0];
  const rows = data.slice(1);
  
  const results = rows.filter(row => row.join(" ").toLowerCase().includes(query.toLowerCase())).map(row => {
    let obj = {};
    headers.forEach((h, i) => obj[h] = row[i]);
    return obj;
  });

  return responseSuccess(results);
}

function backupData() {
  // Simple backup logic: create a copy of the spreadsheet
  const ss = getDatabaseSheet();
  const backupName = SHEET_NAME + "_Backup_" + Utilities.formatDate(new Date(), "GMT+7", "yyyyMMdd");
  const newFile = DriveApp.getFileById(ss.getId()).makeCopy(backupName);
  return responseSuccess({ message: "Backup created: " + backupName, url: newFile.getUrl() });
}

function sendChat(payload) {
  const { id, user, text, timestamp } = payload;
  const ss = getDatabaseSheet();
  let chatSheet = ss.getSheetByName("Chat");
  if (!chatSheet) {
    chatSheet = ss.insertSheet("Chat");
    chatSheet.appendRow(["ID", "User", "Text", "Timestamp"]);
    chatSheet.getRange(1, 1, 1, 4).setFontWeight("bold");
    chatSheet.setFrozenRows(1);
  }
  chatSheet.appendRow([id || new Date().getTime().toString(), user, text, timestamp || new Date().toISOString()]);
  return responseSuccess({ id: id });
}

function getChats(payload) {
  const ss = getDatabaseSheet();
  const chatSheet = ss.getSheetByName("Chat");
  if (!chatSheet || chatSheet.getLastRow() <= 1) {
    return responseSuccess([]);
  }
  const data = chatSheet.getDataRange().getValues();
  const headers = data[0];
  const rows = data.slice(1);
  const messages = rows.map(row => {
    let obj = {};
    headers.forEach((h, i) => obj[h.toLowerCase()] = row[i]);
    return obj;
  });
  return responseSuccess(messages);
}
