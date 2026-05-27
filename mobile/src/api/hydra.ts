import type { HydraCollection } from './types';

/** API Platform JSON-LD or plain { orders: [] } lists */
export function hydraMembers<T>(payload: HydraCollection<T> | T[] | { orders?: T[] }): T[] {
  if (Array.isArray(payload)) {
    return payload;
  }
  if (payload && typeof payload === 'object' && 'orders' in payload && Array.isArray(payload.orders)) {
    return payload.orders;
  }
  const collection = payload as HydraCollection<T>;
  return collection['hydra:member'] ?? collection.member ?? [];
}
