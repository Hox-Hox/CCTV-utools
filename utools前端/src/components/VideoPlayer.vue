<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import videojs from 'video.js';
import Hls from 'hls.js';

const props = defineProps({
  src: {
    type: String,
    required: true
  },
  autoplay: {
    type: Boolean,
    default: true
  },
  volume: {
    type: Number,
    default: 50
  }
});

// 音量变化事件
const emit = defineEmits(['volume-change']);

const videoEl = ref(null);
const videoContainer = ref(null);
let player = null;
let hls = null;
const isPlayerReady = ref(false);
const isLoadingSource = ref(false);
const loadingDelay = ref(null);
const retryCount = ref(0);
const maxRetries = 3;
const uniqueId = ref(`video-player-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`);
const videoContainerClass = computed(() => {
  return {
    'loading': isLoadingSource.value,
    'player-ready': isPlayerReady.value
  };
});

// 计算音量值
const volumeValue = computed(() => {
  return props.volume / 100; // videojs音量范围是0-1
});

// 销毁HLS实例
const destroyHls = () => {
  if (hls) {
    try {
      hls.stopLoad();
      hls.detachMedia();
      hls.destroy();
    } catch (e) {
      console.error('销毁HLS时出错:', e);
    } finally {
      hls = null;
    }
  }
};

// 安全地销毁播放器
const safelyDestroyPlayer = () => {
  isPlayerReady.value = false;
  
  // 清除任何可能的延迟加载
  if (loadingDelay.value) {
    clearTimeout(loadingDelay.value);
    loadingDelay.value = null;
  }
  
  return new Promise((resolve) => {
    try {
      // 先销毁HLS实例
      destroyHls();
      
      if (player) {
        // 移除事件监听器
        try {
          if (player.hasStarted_) {
            player.off('volumechange');
          }
          player.pause();
          player.reset();
        } catch (e) {
          console.error('暂停播放器时出错:', e);
        }
        
        // 给播放器一个短暂的时间来完成暂停操作
        setTimeout(() => {
          try {
            if (player) {
              player.dispose();
            }
          } catch (error) {
            console.error("销毁播放器最终阶段出错:", error);
          } finally {
            player = null;
            resolve();
          }
        }, 150);
      } else {
        resolve();
      }
    } catch (error) {
      console.error("销毁播放器时出错:", error);
      player = null;
      hls = null;
      resolve();
    }
  });
};

// 强制刷新播放器ID
const regeneratePlayerId = () => {
  uniqueId.value = `video-player-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`;
  return nextTick();
};

// 检查DOM元素是否在文档中
const isElementInDOM = (element) => {
  return document.body.contains(element);
};

// 创建新的视频元素
const createVideoElement = () => {
  // 如果容器中已有视频元素，移除它
  if (videoContainer.value) {
    const existingVideo = videoContainer.value.querySelector('video');
    if (existingVideo) {
      videoContainer.value.removeChild(existingVideo);
    }
    
    // 创建新的视频元素
    const newVideo = document.createElement('video');
    newVideo.id = uniqueId.value;
    newVideo.className = 'video-js vjs-default-skin vjs-big-play-centered vjs-theme-city';
    newVideo.controls = true;
    newVideo.preload = 'auto';
    newVideo.width = '100%';
    newVideo.height = '100%';
    
    // 添加到容器中
    videoContainer.value.appendChild(newVideo);
    
    // 更新引用
    videoEl.value = newVideo;
    
    return true;
  }
  return false;
};

// 初始化视频播放器
const initializePlayer = async () => {
  isLoadingSource.value = true;
  retryCount.value = 0;
  
  // 确保完全销毁旧播放器
  await safelyDestroyPlayer();
  
  // 强制刷新播放器DOM元素
  await regeneratePlayerId();
  
  // 确保DOM已经更新
  await nextTick();
  
  // 创建新的视频元素
  if (!createVideoElement()) {
    console.error('无法创建视频元素，容器不存在');
    isLoadingSource.value = false;
    return;
  }
  
  // 短暂延迟以确保DOM已更新
  loadingDelay.value = setTimeout(async () => {
    try {
      if (!videoEl.value || !isElementInDOM(videoEl.value)) {
        console.error("视频元素未找到或不在DOM中");
        isLoadingSource.value = false;
        return;
      }

      // 创建VideoJS播放器
      player = videojs(videoEl.value, {
        controls: true,
        autoplay: props.autoplay,
        fluid: true,
        fill: true,
        preload: 'auto',
        playbackRates: [0.5, 1, 1.5, 2],
        userActions: {
          hotkeys: true
        },
        sources: [{
          src: props.src,
          type: 'application/x-mpegURL'
        }],
        html5: {
          hls: {
            overrideNative: true
          }
        }
      });

      player.ready(() => {
        if (!player || !videoEl.value || !isElementInDOM(videoEl.value)) {
          console.error("播放器准备好后，视频元素不在DOM中");
          return;
        }
        
        // 设置音量
        player.volume(volumeValue.value);
        
        // 监听音量变化
        player.on('volumechange', () => {
          if (!player) return;
          const currentVolume = Math.round(player.volume() * 100);
          emit('volume-change', currentVolume);
        });
        
        // 检查当前浏览器是否支持原生HLS
        if (videoEl.value && videoEl.value.canPlayType('application/vnd.apple.mpegurl')) {
          // 原生支持HLS的浏览器可以直接播放
          player.src({
            src: props.src,
            type: 'application/x-mpegURL'
          });
          
          isPlayerReady.value = true;
          isLoadingSource.value = false;
        } else if (Hls.isSupported()) {
          // 使用hls.js播放m3u8流
          if (hls) {
            destroyHls();
          }
          
          hls = new Hls({
            enableWorker: true,
            lowLatencyMode: true,
            fragLoadingMaxRetry: 5,
            manifestLoadingMaxRetry: 5,
            levelLoadingMaxRetry: 5,
            startLevel: 0,
            debug: false
          });
          
          if (!videoEl.value || !isElementInDOM(videoEl.value)) {
            console.error("HLS初始化时，视频元素不在DOM中");
            isLoadingSource.value = false;
            return;
          }
          
          // 附加媒体
          hls.attachMedia(videoEl.value);
          
          // 监听HLS事件
          hls.on(Hls.Events.MEDIA_ATTACHED, () => {
            console.log('HLS: 媒体已附加');
            if (hls) {
              hls.loadSource(props.src);
            }
          });
          
          hls.on(Hls.Events.MANIFEST_PARSED, (event, data) => {
            console.log('HLS: 清单已解析, 发现', data.levels.length, '个质量级别');
            
            if (!player || !videoEl.value || !isElementInDOM(videoEl.value)) {
              console.error("HLS清单解析后，播放器或视频元素不在DOM中");
              return;
            }
            
            isPlayerReady.value = true;
            isLoadingSource.value = false;
            
            if (props.autoplay) {
              player.play().catch(err => {
                console.warn('自动播放失败，可能需要用户交互:', err);
                // 显示播放按钮提示用户点击
                if (player && player.bigPlayButton) {
                  player.bigPlayButton.show();
                }
              });
            }
          });
          
          // 错误处理
          hls.on(Hls.Events.ERROR, (event, data) => {
            console.error('HLS error:', data);
            if (data.fatal) {
              switch (data.type) {
                case Hls.ErrorTypes.NETWORK_ERROR:
                  // 尝试恢复网络错误
                  console.log('网络错误，尝试恢复...');
                  if (hls) {
                    hls.startLoad();
                  }
                  break;
                case Hls.ErrorTypes.MEDIA_ERROR:
                  console.log('媒体错误，尝试恢复...');
                  if (hls) {
                    hls.recoverMediaError();
                  }
                  break;
                default:
                  // 无法恢复的错误
                  console.error('无法恢复的HLS错误，尝试重新初始化播放器');
                  retryInitialization();
                  break;
              }
            }
          });
        } else {
          console.error('当前浏览器不支持HLS');
          isPlayerReady.value = true;
          isLoadingSource.value = false;
        }
      });

      // 监听播放器错误
      player.on('error', (error) => {
        console.error('播放器错误:', error);
        retryInitialization();
      });
    } catch (error) {
      console.error("初始化播放器时出错:", error);
      isLoadingSource.value = false;
      retryInitialization();
    }
  }, 150);
};

// 重试初始化
const retryInitialization = () => {
  if (retryCount.value < maxRetries) {
    retryCount.value++;
    console.log(`尝试重新初始化播放器 (${retryCount.value}/${maxRetries})...`);
    
    // 增加延迟，避免频繁重试
    const delay = 1000 * retryCount.value;
    
    setTimeout(async () => {
      await regeneratePlayerId();
      initializePlayer();
    }, delay);
  } else {
    console.error(`重试次数已达最大值 (${maxRetries})，无法恢复播放器`);
    isLoadingSource.value = false;
  }
};

// 监听源地址变化
watch(() => props.src, async (newSrc, oldSrc) => {
  if (newSrc && newSrc !== oldSrc) {
    console.log(`源地址已更改: ${oldSrc} -> ${newSrc}`);
    // 重置重试计数
    retryCount.value = 0;
    
    // 初始化新的播放器实例
    await initializePlayer();
  }
}, { immediate: false });

// 监听音量变化
watch(() => props.volume, (newVolume) => {
  if (player && isPlayerReady.value) {
    player.volume(newVolume / 100);
  }
});

// 监听自动播放设置变化
watch(() => props.autoplay, (newAutoplay) => {
  if (player && isPlayerReady.value) {
    player.autoplay(newAutoplay);
    
    // 如果设置为自动播放并且视频已加载但未播放，则开始播放
    if (newAutoplay && player.readyState() > 0 && player.paused()) {
      player.play().catch(err => {
        console.warn('播放失败，可能需要用户交互:', err);
      });
    }
  }
});

onMounted(async () => {
  if (props.src) {
    await nextTick();
    await initializePlayer();
  }
});

onBeforeUnmount(async () => {
  await safelyDestroyPlayer();
});
</script>

<template>
  <div class="video-player-container" :class="videoContainerClass" ref="videoContainer">
    <div class="loading-indicator" v-if="isLoadingSource"></div>
    <!-- 视频元素将在JavaScript中动态创建 -->
  </div>
</template>

<style scoped>
.video-player-container {
  width: 100%;
  height: 100%;
  background-color: #000;
  overflow: hidden;
  position: relative;
}

.loading-indicator {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 50px;
  height: 50px;
  border: 3px solid var(--primary-color, #409EFF);
  border-radius: 50%;
  border-top-color: transparent;
  animation: spin 1s linear infinite;
  z-index: 2;
}

@keyframes spin {
  to { transform: translate(-50%, -50%) rotate(360deg); }
}

:deep(.vjs-theme-city) {
  --vjs-theme-city--primary: var(--primary-color, #409EFF);
}

:deep(.video-js) {
  width: 100%;
  height: 100%;
  visibility: hidden;
  opacity: 0;
  transition: opacity 0.3s, visibility 0.3s;
}

.player-ready :deep(.video-js) {
  visibility: visible;
  opacity: 1;
}

:deep(.vjs-control-bar) {
  background-color: rgba(43, 43, 43, 0.7);
  backdrop-filter: blur(5px);
}

:deep(.vjs-big-play-button) {
  background-color: rgba(64, 158, 255, 0.7);
  border-color: #409EFF;
  border-radius: 50%;
  height: 60px;
  width: 60px;
  line-height: 60px;
  transform: translate(-50%, -50%);
  transition: transform 0.2s, background-color 0.2s;
}

:deep(.vjs-big-play-button:hover) {
  background-color: rgba(64, 158, 255, 0.9);
  transform: translate(-50%, -50%) scale(1.1);
}

:deep(.vjs-volume-level) {
  background-color: var(--primary-color, #409EFF);
}

:deep(.vjs-play-progress) {
  background-color: var(--primary-color, #409EFF);
}

:deep(.vjs-slider-bar) {
  background-color: var(--primary-color, #409EFF);
}

:deep(.vjs-menu-button-popup .vjs-menu) {
  background-color: rgba(43, 43, 43, 0.9);
  backdrop-filter: blur(5px);
}

:deep(.vjs-loading-spinner) {
  border-color: var(--primary-color, #409EFF);
  border-top-color: transparent;
}
</style> 