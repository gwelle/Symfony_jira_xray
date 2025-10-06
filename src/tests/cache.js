const userCache = new Map();

export function storeUserId(key, id) {
  userCache.set(key, id);
}

export function getUserId(key) {
  return userCache.get(key);
}

export function clearUserId(key) {
  userCache.delete(key);
}
