const fs = require('node:fs')
const path = require('node:path')
const { net } = require('electron')

// 通过 window 对象向渲染进程注入 nodejs 能力
window.services = {
  // 读文件
  readFile (file) {
    return fs.readFileSync(file, { encoding: 'utf-8' })
  },
  // 文本写入到下载目录
  writeTextFile (text) {
    const filePath = path.join(window.utools.getPath('downloads'), Date.now().toString() + '.txt')
    fs.writeFileSync(filePath, text, { encoding: 'utf-8' })
    return filePath
  },
  // 图片写入到下载目录
  writeImageFile (base64Url) {
    const matchs = /^data:image\/([a-z]{1,20});base64,/i.exec(base64Url)
    if (!matchs) return
    const filePath = path.join(window.utools.getPath('downloads'), Date.now().toString() + '.' + matchs[1])
    fs.writeFileSync(filePath, base64Url.substring(matchs[0].length), { encoding: 'base64' })
    return filePath
  },
  
  // 获取CCTV频道列表
  async fetchChannels() {
    return new Promise((resolve, reject) => {
      const request = net.request('http://cctv.hoxhox.cn/api/streams.php');
      let data = '';
      
      request.on('response', (response) => {
        response.on('data', (chunk) => {
          data += chunk.toString();
        });
        
        response.on('end', () => {
          try {
            const result = JSON.parse(data);
            resolve(result);
          } catch (error) {
            reject(error);
          }
        });
      });
      
      request.on('error', (error) => {
        reject(error);
      });
      
      request.end();
    });
  },
  
  // 保存用户收藏的频道
  saveUserFavorites(favorites) {
    try {
      const userDataPath = window.utools.getPath('userData');
      const favoritesPath = path.join(userDataPath, 'cctv-favorites.json');
      fs.writeFileSync(favoritesPath, JSON.stringify(favorites), { encoding: 'utf-8' });
      return true;
    } catch (error) {
      console.error('保存收藏失败:', error);
      return false;
    }
  },
  
  // 获取用户收藏的频道
  getUserFavorites() {
    try {
      const userDataPath = window.utools.getPath('userData');
      const favoritesPath = path.join(userDataPath, 'cctv-favorites.json');
      
      if (fs.existsSync(favoritesPath)) {
        const data = fs.readFileSync(favoritesPath, { encoding: 'utf-8' });
        return JSON.parse(data);
      }
      
      return [];
    } catch (error) {
      console.error('获取收藏失败:', error);
      return [];
    }
  }
}
