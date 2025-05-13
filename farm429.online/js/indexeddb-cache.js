const DB_NAME = 'farmCacheDB';
const DB_VERSION = 1;
const STORES = {
  SENSOR_DATA: 'sensorData',
  PRESET_MODES: 'presetModes',
  EVENTS: 'events',
  SETTINGS: 'settings'
};
const MAX_CACHE_AGE = 24 * 60 * 60 * 1000;
/**
 * Инициализация базы данных IndexedDB
 * @returns {Promise} Промис, возвращающий объект базы данных
 */
function initDatabase() {
  return new Promise((resolve, reject) => {
    if (!('indexedDB' in window)) {
      console.warn('Ваш браузер не поддерживает IndexedDB. Кэширование данных недоступно.');
      return reject('IndexedDB не поддерживается');
    }
    const request = indexedDB.open(DB_NAME, DB_VERSION);
    request.onerror = (event) => {
      console.error('Ошибка открытия IndexedDB:', event.target.error);
      reject(event.target.error);
    };
    request.onsuccess = (event) => {
      const db = event.target.result;
      console.log('Подключение к IndexedDB успешно установлено');
      db.onerror = (event) => {
        console.error('Ошибка базы данных:', event.target.error);
      };
      resolve(db);
    };
    request.onupgradeneeded = (event) => {
      const db = event.target.result;
      if (!db.objectStoreNames.contains(STORES.SENSOR_DATA)) {
        const sensorStore = db.createObjectStore(STORES.SENSOR_DATA, { keyPath: 'timestamp' });
        sensorStore.createIndex('user_id', 'user_id', { unique: false });
        sensorStore.createIndex('type', 'type', { unique: false });
      }
      if (!db.objectStoreNames.contains(STORES.PRESET_MODES)) {
        const modesStore = db.createObjectStore(STORES.PRESET_MODES, { keyPath: 'id' });
        modesStore.createIndex('user_id', 'user_id', { unique: false });
      }
      if (!db.objectStoreNames.contains(STORES.EVENTS)) {
        const eventsStore = db.createObjectStore(STORES.EVENTS, { keyPath: 'id', autoIncrement: true });
        eventsStore.createIndex('user_id', 'user_id', { unique: false });
        eventsStore.createIndex('date', 'date', { unique: false });
      }
      if (!db.objectStoreNames.contains(STORES.SETTINGS)) {
        const settingsStore = db.createObjectStore(STORES.SETTINGS, { keyPath: 'key' });
        settingsStore.createIndex('user_id', 'user_id', { unique: false });
      }
      console.log('Хранилища IndexedDB успешно созданы');
    };
  });
}
/**
 * @param {string} storeName
 * @param {Object|Array} data
 * @returns {Promise}
 */
function saveToCache(storeName, data) {
  return new Promise((resolve, reject) => {
    initDatabase().then(db => {
      if (!db.objectStoreNames.contains(storeName)) {
        return reject(`Хранилище "${storeName}" не существует`);
      }
      const transaction = db.transaction([storeName], 'readwrite');
      const store = transaction.objectStore(storeName);
      const timestamp = Date.now();
      if (Array.isArray(data)) {
        let promises = data.map(item => {
          item.cached_at = timestamp;
          return new Promise((resolve, reject) => {
            const request = store.put(item);
            request.onsuccess = resolve;
            request.onerror = reject;
          });
        });
        Promise.all(promises)
          .then(() => resolve('Данные успешно сохранены в кэш'))
          .catch(error => reject(error));
      } else {
        data.cached_at = timestamp;
        const request = store.put(data);
        request.onsuccess = () => resolve('Данные успешно сохранены в кэш');
        request.onerror = (event) => reject(event.target.error);
      }
    }).catch(reject);
  });
}
/**
 * Получение данных из кэша
 * @param {string} storeName - Название хранилища
 * @param {string|number} key - Ключ для получения конкретной записи (опционально)
 * @param {Object} indexDetails - Детали индекса для поиска {indexName, value} (опционально)
 * @returns {Promise} Промис, возвращающий запрошенные данные
 */
function getFromCache(storeName, key = null, indexDetails = null) {
  return new Promise((resolve, reject) => {
    initDatabase().then(db => {
      if (!db.objectStoreNames.contains(storeName)) {
        return reject(`Хранилище "${storeName}" не существует`);
      }
      const transaction = db.transaction([storeName], 'readonly');
      const store = transaction.objectStore(storeName);
      let request;
      if (key) {
        request = store.get(key);
        request.onsuccess = (event) => {
          const data = event.target.result;
          if (!data) {
            return resolve(null); 
          }
          if (isDataFresh(data)) {
            resolve(data);
          } else {
            deleteFromCache(storeName, key)
              .then(() => resolve(null))
              .catch(error => console.warn('Ошибка при очистке устаревшего кэша:', error));
          }
        };
      } else if (indexDetails) {
        const index = store.index(indexDetails.indexName);
        request = index.getAll(indexDetails.value);
        request.onsuccess = (event) => {
          const data = event.target.result;
          if (!data || data.length === 0) {
            return resolve([]); 
          }
          const freshData = data.filter(item => isDataFresh(item));
          if (freshData.length < data.length) {
            const outdatedKeys = data
              .filter(item => !isDataFresh(item))
              .map(item => item[store.keyPath]);
            Promise.all(outdatedKeys.map(key => deleteFromCache(storeName, key)))
              .catch(error => console.warn('Ошибка при очистке устаревшего кэша:', error));
          }
          resolve(freshData);
        };
      } else {
        request = store.getAll();
        request.onsuccess = (event) => {
          const data = event.target.result;
          if (!data || data.length === 0) {
            return resolve([]); 
          }
          const freshData = data.filter(item => isDataFresh(item));
          if (freshData.length < data.length) {
            const outdatedKeys = data
              .filter(item => !isDataFresh(item))
              .map(item => item[store.keyPath]);
            Promise.all(outdatedKeys.map(key => deleteFromCache(storeName, key)))
              .catch(error => console.warn('Ошибка при очистке устаревшего кэша:', error));
          }
          resolve(freshData);
        };
      }
      request.onerror = (event) => reject(event.target.error);
    }).catch(reject);
  });
}
/**
 * Удаление данных из кэша
 * @param {string} storeName - Название хранилища
 * @param {string|number} key - Ключ записи для удаления
 * @returns {Promise} Промис, завершающийся после удаления данных
 */
function deleteFromCache(storeName, key) {
  return new Promise((resolve, reject) => {
    initDatabase().then(db => {
      if (!db.objectStoreNames.contains(storeName)) {
        return reject(`Хранилище "${storeName}" не существует`);
      }
      const transaction = db.transaction([storeName], 'readwrite');
      const store = transaction.objectStore(storeName);
      const request = store.delete(key);
      request.onsuccess = () => resolve('Данные успешно удалены из кэша');
      request.onerror = (event) => reject(event.target.error);
    }).catch(reject);
  });
}
/**
 * Очистка всего хранилища
 * @param {string} storeName - Название хранилища
 * @returns {Promise} Промис, завершающийся после очистки хранилища
 */
function clearStore(storeName) {
  return new Promise((resolve, reject) => {
    initDatabase().then(db => {
      if (!db.objectStoreNames.contains(storeName)) {
        return reject(`Хранилище "${storeName}" не существует`);
      }
      const transaction = db.transaction([storeName], 'readwrite');
      const store = transaction.objectStore(storeName);
      const request = store.clear();
      request.onsuccess = () => resolve('Хранилище успешно очищено');
      request.onerror = (event) => reject(event.target.error);
    }).catch(reject);
  });
}
/**
 * Проверка актуальности данных в кэше
 * @param {Object} data - Объект данных с полем cached_at
 * @returns {boolean} true если данные актуальны, false если устарели
 */
function isDataFresh(data) {
  if (!data || !data.cached_at) return false;
  const now = Date.now();
  const age = now - data.cached_at;
  return age < MAX_CACHE_AGE;
}
/**
 * Получение данных с сервера или из кэша
 * @param {string} apiUrl - URL API для получения данных
 * @param {string} storeName - Название хранилища
 * @param {Object} options - Опции запроса (метод, заголовки, тело)
 * @param {Object} cacheOptions - Опции кэширования {key, indexName, indexValue}
 * @returns {Promise} Промис, возвращающий данные
 */
function fetchDataWithCache(apiUrl, storeName, options = {}, cacheOptions = {}) {
  return new Promise((resolve, reject) => {
    const defaultOptions = {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json'
      },
      credentials: 'include'
    };
    const fetchOptions = { ...defaultOptions, ...options };
    let cachePromise;
    if (cacheOptions.key) {
      cachePromise = getFromCache(storeName, cacheOptions.key);
    } else if (cacheOptions.indexName && cacheOptions.indexValue) {
      cachePromise = getFromCache(storeName, null, {
        indexName: cacheOptions.indexName,
        value: cacheOptions.indexValue
      });
    } else {
      cachePromise = getFromCache(storeName);
    }
    cachePromise
      .then(cachedData => {
        if (cachedData && (cachedData.length > 0 || Object.keys(cachedData).length > 0)) {
          console.log('Данные загружены из кэша:', storeName);
          resolve(cachedData);
          fetchAndUpdateCache();
        } else {
          fetchAndUpdateCache()
            .then(data => resolve(data))
            .catch(error => reject(error));
        }
      })
      .catch(error => {
        console.warn('Ошибка при получении данных из кэша:', error);
        fetchAndUpdateCache()
          .then(data => resolve(data))
          .catch(error => reject(error));
      });
    function fetchAndUpdateCache() {
      return fetch(apiUrl, fetchOptions)
        .then(response => {
          if (!response.ok) {
            throw new Error(`Ошибка HTTP: ${response.status}`);
          }
          return response.json();
        })
        .then(data => {
          saveToCache(storeName, data)
            .catch(error => console.warn('Ошибка при сохранении данных в кэш:', error));
          return data;
        });
    }
  });
}
window.cacheManager = {
  initDatabase,
  saveToCache,
  getFromCache,
  deleteFromCache,
  clearStore,
  fetchDataWithCache,
  STORES
}; 