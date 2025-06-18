<template>
  <el-drawer
    v-model="visible"
    title="设置"
    size="320px"
    :show-close="true"
    :destroy-on-close="false"
    :append-to-body="true"
    custom-class="settings-drawer"
    :before-close="handleClose"
    :modal-class="isDarkMode ? 'dark-modal' : ''"
  >
    <div class="settings-container">
      <div class="settings-header">
        <h3>界面设置</h3>
      </div>
      
      <div class="settings-section">
        <el-switch
          v-model="settings.darkMode"
          class="settings-item"
          active-text="暗色模式"
          inactive-text="浅色模式"
          @change="updateSettings('darkMode')"
        />
        
        <div class="settings-divider"></div>
        
        <el-switch
          v-model="settings.compactMode"
          class="settings-item"
          active-text="紧凑模式"
          inactive-text="标准模式"
          @change="updateSettings('compactMode')"
        />
      </div>

      <div class="settings-header">
        <h3>播放设置</h3>
      </div>
      
      <div class="settings-section">
        <el-switch
          v-model="settings.multipleWindows"
          class="settings-item"
          active-text="允许多窗口"
          inactive-text="单窗口模式"
          @change="updateSettings('multipleWindows')"
        />
        
        <div class="settings-divider"></div>
        
        <el-switch
          v-model="settings.floatingWindows"
          class="settings-item"
          active-text="窗口悬浮"
          inactive-text="固定窗口"
          @change="updateSettings('floatingWindows')"
        />
        
        <div class="settings-divider"></div>
        
        <el-switch
          v-model="settings.autoPlay"
          class="settings-item"
          active-text="自动播放"
          inactive-text="手动播放"
          @change="updateSettings('autoPlay')"
        />
      </div>

      <div class="settings-header">
        <h3>常规设置</h3>
      </div>
      
      <div class="settings-section">
        <div class="settings-item with-slider">
          <span class="settings-label">默认音量</span>
          <el-slider 
            v-model="settings.defaultVolume" 
            :min="0" 
            :max="100" 
            :step="1" 
            :show-tooltip="true"
            :format-tooltip="value => `${value}%`"
            @change="updateSettings('defaultVolume')"
          />
        </div>
        
        <div class="settings-divider"></div>
        
        <div class="settings-item with-select">
          <span class="settings-label">窗口大小</span>
          <el-select 
            v-model="settings.defaultWindowSize" 
            placeholder="选择窗口大小"
            @change="updateSettings('defaultWindowSize')"
          >
            <el-option label="小窗口" value="small" />
            <el-option label="中等窗口" value="medium" />
            <el-option label="大窗口" value="large" />
          </el-select>
        </div>
        
        <div class="settings-divider"></div>
        
        <div class="settings-item">
          <span class="settings-label">自动保存设置</span>
          <el-switch
            v-model="autoSave"
            class="settings-switch"
            @change="toggleAutoSave"
          />
        </div>
      </div>

      <div class="settings-actions">
        <el-button type="primary" @click="saveSettings">保存设置</el-button>
        <el-button @click="resetSettings">恢复默认</el-button>
      </div>
      
      <div class="settings-footer">
        <p class="settings-version">CCTV直播聚合 v1.0.0</p>
      </div>
    </div>
  </el-drawer>
</template>

<script setup>
import { ref, reactive, defineExpose, defineEmits, watch, computed } from 'vue';
import { ElMessage } from 'element-plus';

const emit = defineEmits(['update-settings']);

// 默认设置
const defaultSettings = {
  darkMode: true,
  compactMode: false,
  multipleWindows: true,
  floatingWindows: true,
  autoPlay: true,
  defaultVolume: 50,
  defaultWindowSize: 'medium'
};

// 当前设置
const settings = reactive({ ...defaultSettings });

// 控制面板显示状态
const visible = ref(false);

// 自动保存开关
const autoSave = ref(true);

// 计算当前是否为暗色模式
const isDarkMode = computed(() => settings.darkMode);

// 创建可安全序列化的设置对象
const createSerializableSettings = () => {
  return JSON.parse(JSON.stringify({
    darkMode: settings.darkMode,
    compactMode: settings.compactMode,
    multipleWindows: settings.multipleWindows,
    floatingWindows: settings.floatingWindows,
    autoPlay: settings.autoPlay,
    defaultVolume: settings.defaultVolume,
    defaultWindowSize: settings.defaultWindowSize
  }));
};

// 处理抽屉关闭前的回调
const handleClose = (done) => {
  if (autoSave.value) {
    // 自动保存所有设置
    const result = saveSettingsToStorage();
    
    if (result) {
      // 通知父组件所有设置已更新
      const serializableSettings = createSerializableSettings();
      emit('update-settings', { all: true, settings: serializableSettings });
      
      // 显示保存成功提示
      ElMessage({
        message: '设置已自动保存',
        type: 'success',
        duration: 2000
      });
    }
  }
  
  // 允许关闭
  done();
};

// 切换自动保存
const toggleAutoSave = (value) => {
  autoSave.value = value;
  
  // 保存自动保存设置
  try {
    if (window.utools) {
      window.utools.dbStorage.setItem('cctv-auto-save', value);
    } else {
      localStorage.setItem('cctv-auto-save', JSON.stringify(value));
    }
    
    // 显示提示
    ElMessage({
      message: value ? '已开启自动保存' : '已关闭自动保存',
      type: 'info',
      duration: 2000
    });
  } catch (error) {
    console.error('保存自动保存设置失败:', error);
    ElMessage({
      message: '保存设置失败',
      type: 'error',
      duration: 2000
    });
  }
};

// 加载自动保存设置
const loadAutoSaveSettings = () => {
  let savedAutoSave = null;
  
  try {
    if (window.utools) {
      savedAutoSave = window.utools.dbStorage.getItem('cctv-auto-save');
    } else {
      const autoSaveJson = localStorage.getItem('cctv-auto-save');
      if (autoSaveJson) {
        savedAutoSave = JSON.parse(autoSaveJson);
      }
    }
    
    if (savedAutoSave !== null) {
      autoSave.value = savedAutoSave;
    }
  } catch (e) {
    console.error('解析自动保存设置出错:', e);
  }
};

// 更新设置
const updateSettings = (key) => {
  try {
    // 实时将更改通知父组件，确保发送简单值类型
    let value = settings[key];
    
    // 如果值是对象，进行安全的序列化和反序列化
    if (typeof value === 'object' && value !== null) {
      value = JSON.parse(JSON.stringify(value));
    }
    
    emit('update-settings', { key, value });
    
    // 如果开启了自动保存，保存到本地存储
    if (autoSave.value) {
      saveSettingsToStorage();
    }
  } catch (error) {
    console.error('更新设置失败:', error);
    ElMessage({
      message: '更新设置失败',
      type: 'error',
      duration: 2000
    });
  }
};

// 保存设置到本地存储
const saveSettingsToStorage = () => {
  try {
    // 创建一个纯粹的数据对象，避免代理对象的序列化问题
    const serializableSettings = createSerializableSettings();
    
    if (window.utools) {
      // 使用uTools API保存设置
      window.utools.dbStorage.setItem('cctv-settings', serializableSettings);
    } else {
      // 回退到localStorage
      localStorage.setItem('cctv-settings', JSON.stringify(serializableSettings));
    }
    return true;
  } catch (error) {
    console.error('保存设置失败:', error);
    ElMessage({
      message: '保存设置失败: ' + error.message,
      type: 'error',
      duration: 3000
    });
    return false;
  }
};

// 从本地存储加载设置
const loadSettingsFromStorage = () => {
  let savedSettings = null;
  
  try {
    if (window.utools) {
      // 使用uTools API获取设置
      savedSettings = window.utools.dbStorage.getItem('cctv-settings');
    } else {
      // 回退到localStorage
      const settingsJson = localStorage.getItem('cctv-settings');
      if (settingsJson) {
        savedSettings = JSON.parse(settingsJson);
      }
    }
    
    if (savedSettings) {
      // 合并保存的设置和默认设置，确保新增的设置项也有默认值
      Object.assign(settings, defaultSettings, savedSettings);
    }
    return true;
  } catch (e) {
    console.error('加载设置出错:', e);
    return false;
  }
};

// 保存所有设置
const saveSettings = () => {
  try {
    const result = saveSettingsToStorage();
    
    if (result) {
      // 通知父组件所有设置已更新
      const serializableSettings = createSerializableSettings();
      emit('update-settings', { all: true, settings: serializableSettings });
      
      // 关闭设置面板
      visible.value = false;
      
      // 显示保存成功提示
      ElMessage({
        message: '设置已保存',
        type: 'success',
        duration: 2000
      });
    } else {
      ElMessage({
        message: '保存设置失败',
        type: 'error',
        duration: 2000
      });
    }
  } catch (error) {
    console.error('保存设置出错:', error);
    ElMessage({
      message: '保存设置失败: ' + error.message,
      type: 'error',
      duration: 3000
    });
  }
};

// 重置所有设置到默认值
const resetSettings = () => {
  try {
    Object.assign(settings, defaultSettings);
    const result = saveSettingsToStorage();
    
    if (result) {
      // 通知父组件所有设置已更新
      const serializableSettings = createSerializableSettings();
      emit('update-settings', { all: true, settings: serializableSettings });
      
      // 显示重置成功提示
      ElMessage({
        message: '设置已恢复默认',
        type: 'success',
        duration: 2000
      });
    } else {
      ElMessage({
        message: '重置设置失败',
        type: 'error',
        duration: 2000
      });
    }
  } catch (error) {
    console.error('重置设置出错:', error);
    ElMessage({
      message: '重置设置失败: ' + error.message,
      type: 'error',
      duration: 3000
    });
  }
};

// 显示设置面板
const showSettings = () => {
  loadSettingsFromStorage(); // 确保显示最新设置
  loadAutoSaveSettings(); // 加载自动保存设置
  visible.value = true;
};

// 关闭设置面板
const hideSettings = () => {
  visible.value = false;
};

// 暴露方法给父组件
defineExpose({
  showSettings,
  hideSettings,
  getSettings: () => createSerializableSettings()
});

// 初始加载设置
loadSettingsFromStorage();
loadAutoSaveSettings();
</script>

<style>
/* 全局样式，确保模态背景在黑暗模式下也能正确显示 */
.dark-modal {
  background-color: rgba(0, 0, 0, 0.85) !important;
  backdrop-filter: blur(4px);
}

.el-overlay {
  background-color: rgba(0, 0, 0, 0.7) !important;
}

/* 确保黑色背景 */
.el-drawer {
  background-color: black !important;
}

.el-drawer__header {
  background-color: black !important;
  color: white !important;
  margin-bottom: 0 !important;
  padding: 16px !important;
  border-bottom: 1px solid var(--border-color, #444444) !important;
}

.el-drawer__title {
  color: white !important;
  font-weight: 600 !important;
}

.el-drawer__close-btn {
  color: white !important;
}

.el-drawer__body {
  background-color: black !important;
  color: white !important;
  padding: 0 !important;
}

/* 确保消息提示在黑暗模式下正确显示 */
.el-message {
  background-color: var(--card-bg, #2b2b2b) !important;
  border-color: var(--border-color, #444444) !important;
  color: var(--text-color, #e0e0e0) !important;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.4) !important;
}

.el-message--success {
  background-color: rgba(103, 194, 58, 0.1) !important;
  border-color: rgba(103, 194, 58, 0.2) !important;
}

.el-message--error {
  background-color: rgba(245, 108, 108, 0.1) !important;
  border-color: rgba(245, 108, 108, 0.2) !important;
}

.el-message--info {
  background-color: rgba(144, 147, 153, 0.1) !important;
  border-color: rgba(144, 147, 153, 0.2) !important;
}

.el-message__content {
  color: var(--text-color, #e0e0e0) !important;
}

.el-message .el-message__icon {
  color: inherit !important;
}

/* 修复弹窗中的下拉菜单在暗色主题下的显示 */
.el-popper.is-light {
  background-color: var(--card-bg, #2b2b2b) !important;
  border-color: var(--border-color, #444444) !important;
  color: var(--text-color, #e0e0e0) !important;
}

.el-select-dropdown__item {
  color: var(--text-color, #e0e0e0) !important;
}

.el-select-dropdown__item:hover {
  background-color: rgba(64, 158, 255, 0.1) !important;
}

.el-select-dropdown__item.selected {
  color: var(--primary-color, #409EFF) !important;
}
</style>

<style scoped>
.settings-container {
  height: 100%;
  display: flex;
  flex-direction: column;
  padding: 0 16px;
}

.settings-header {
  margin: 20px 0 8px 0;
}

.settings-header h3 {
  font-size: 16px;
  font-weight: 600;
  color: var(--text-color);
  margin: 0;
  position: relative;
  padding-left: 12px;
}

.settings-header h3::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 4px;
  height: 16px;
  background-color: var(--primary-color, #409EFF);
  border-radius: 2px;
}

.settings-section {
  background-color: var(--card-bg);
  border-radius: 8px;
  padding: 12px 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  transition: box-shadow 0.3s ease;
}

.settings-section:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.settings-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
}

.settings-label {
  font-size: 14px;
  color: var(--text-color);
}

.settings-divider {
  height: 1px;
  background-color: var(--border-color);
  margin: 8px 0;
  opacity: 0.6;
}

.settings-item.with-slider {
  flex-direction: column;
  align-items: flex-start;
}

.settings-item.with-slider .el-slider {
  width: 100%;
  margin-top: 12px;
}

.settings-item.with-select {
  margin: 12px 0;
}

.settings-actions {
  margin-top: 20px;
  padding: 16px 0;
  display: flex;
  justify-content: space-between;
  gap: 12px;
}

.settings-footer {
  margin-top: auto;
  padding: 16px 0;
  text-align: center;
  color: var(--secondary-text-color);
  font-size: 12px;
  opacity: 0.7;
}

.settings-version {
  margin: 0;
}

/* 修复Element Plus的样式，使其适应暗色主题 */
:deep(.el-drawer__body) {
  padding: 0;
  overflow-y: auto;
  background-color: var(--background-color);
  color: var(--text-color);
}

:deep(.el-drawer__header) {
  margin-bottom: 0;
  padding: 16px;
  border-bottom: 1px solid var(--border-color);
  background-color: var(--card-bg);
  color: var(--text-color);
}

:deep(.el-drawer__title) {
  color: var(--text-color);
  font-weight: 600;
}

:deep(.el-drawer__close-btn) {
  color: var(--text-color);
}

:deep(.el-switch__label) {
  color: var(--text-color);
}

:deep(.el-select) {
  width: 120px;
}

:deep(.el-select-dropdown) {
  background-color: var(--card-bg) !important;
  border-color: var(--border-color) !important;
}

:deep(.el-select-dropdown__item) {
  color: var(--text-color) !important;
}

:deep(.el-select-dropdown__item.selected) {
  color: var(--primary-color, #409EFF) !important;
  background-color: rgba(64, 158, 255, 0.1) !important;
}

:deep(.el-select-dropdown__item:hover) {
  background-color: rgba(64, 158, 255, 0.05) !important;
}

:deep(.el-select .el-input__inner) {
  background-color: var(--card-bg) !important;
  border-color: var(--border-color) !important;
  color: var(--text-color) !important;
}

:deep(.el-button) {
  flex: 1;
}

:deep(.el-slider__runway) {
  background-color: rgba(127, 127, 127, 0.3);
}

:deep(.el-slider__bar) {
  background-color: var(--primary-color, #409EFF);
}

:deep(.el-slider__button) {
  border-color: var(--primary-color, #409EFF);
}
</style> 