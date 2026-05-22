import type { HydraCollection } from './types';

export function hydraMembers<T>(payload: HydraCollection<T> | T[]): T[] {
  if (Array.isArray(payload)) {
    return payload;
  }
  return payload['hydra:member'] ?? payload.member ?? [];
}
