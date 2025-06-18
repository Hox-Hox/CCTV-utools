import axios from 'axios';

// CCTV频道API地址
const API_URL = 'http://cctv.hoxhox.cn/api/streams.php';

// 缓存TTL (毫秒)
const CACHE_TTL = 5 * 60 * 1000; // 5分钟

// 频道数据缓存
let channelsCache = {
  data: null,
  timestamp: 0
};

// 自定义错误处理的axios实例
const axiosInstance = axios.create({
  timeout: 10000, // 10秒超时
  headers: {
    'Content-Type': 'application/json'
  }
});

// 添加重试拦截器
axiosInstance.interceptors.response.use(null, async (error) => {
  const config = error.config;
  
  // 如果没有设置重试次数或已达到最大次数，则拒绝
  if (!config || !config.retry || config.retryCount >= config.retry) {
    return Promise.reject(error);
  }
  
  // 设置重试计数
  config.retryCount = config.retryCount || 0;
  config.retryCount += 1;
  
  // 创建延迟
  const delay = new Promise(resolve => setTimeout(resolve, config.retryDelay || 1000));
  
  // 等待后重试
  await delay;
  return axiosInstance(config);
});

/**
 * 获取所有频道列表
 * @param {boolean} forceRefresh 是否强制刷新缓存
 * @returns {Promise<Array>} 频道列表
 */
export async function getAllChannels(forceRefresh = false) {
  // 检查缓存是否有效
  const now = Date.now();
  if (!forceRefresh && channelsCache.data && (now - channelsCache.timestamp < CACHE_TTL)) {
    return channelsCache.data;
  }
  
  try {
    const response = await axiosInstance.get(API_URL, {
      retry: 3, // 最多重试3次
      retryDelay: 1000 // 重试间隔1秒
    });
    
    if (response.data && response.data.code === 200) {
      // 更新缓存
      const channels = response.data.data || [];
      channelsCache = {
        data: channels,
        timestamp: now
      };
      
      return channels;
    } else {
      console.error('获取频道数据失败:', response.data?.message || '未知错误');
      
      // 如果缓存数据存在，返回缓存
      if (channelsCache.data) {
        console.log('使用缓存数据');
        return channelsCache.data;
      }
      
      return [];
    }
  } catch (error) {
    console.error('请求频道API出错:', error);
    
    // 如果缓存数据存在，返回缓存
    if (channelsCache.data) {
      console.log('API请求失败，使用缓存数据');
      return channelsCache.data;
    }
    
    return [];
  }
}

/**
 * 根据分类获取频道
 * @param {string} categoryName 分类名称
 * @returns {Promise<Array>} 指定分类的频道列表
 */
export async function getChannelsByCategory(categoryName) {
  try {
    const channels = await getAllChannels();
    return channels.filter(channel => channel.category_name === categoryName);
  } catch (error) {
    console.error('获取分类频道出错:', error);
    return [];
  }
}

/**
 * 获取所有频道分类
 * @returns {Promise<Array>} 分类列表
 */
export async function getAllCategories() {
  try {
    const channels = await getAllChannels();
    const categories = [...new Set(channels.map(channel => channel.category_name))];
    return categories;
  } catch (error) {
    console.error('获取频道分类出错:', error);
    return [];
  }
}

/**
 * 搜索频道
 * @param {string} keyword 搜索关键词
 * @returns {Promise<Array>} 匹配的频道列表
 */
export async function searchChannels(keyword) {
  if (!keyword) return [];
  
  try {
    const channels = await getAllChannels();
    const lowerKeyword = keyword.toLowerCase();
    
    return channels.filter(channel => {
      return channel.name.toLowerCase().includes(lowerKeyword) ||
             channel.category_name.toLowerCase().includes(lowerKeyword);
    });
  } catch (error) {
    console.error('搜索频道出错:', error);
    return [];
  }
}

/**
 * 获取频道详情
 * @param {string} channelId 频道ID
 * @returns {Promise<Object|null>} 频道详情
 */
export async function getChannelById(channelId) {
  try {
    const channels = await getAllChannels();
    return channels.find(channel => channel.id === channelId) || null;
  } catch (error) {
    console.error('获取频道详情出错:', error);
    return null;
  }
}

/**
 * 清除频道缓存
 * @returns {void}
 */
export function clearChannelCache() {
  channelsCache = {
    data: null,
    timestamp: 0
  };
}

/**
 * 刷新频道列表
 * @returns {Promise<Array>} 刷新后的频道列表
 */
export async function refreshChannels() {
  return getAllChannels(true);
}