const userCache = new Map();

export function storeUserId(key, id) {
  userCache.set(key, id);
}

export function getUserId(key) {
  const id = userCache.get(key);
  if (!id) throw new Error(`No userId stored for key "${key}"`);
  return id;
}

export function clearUserId(key) {
  userCache.delete(key);
}
