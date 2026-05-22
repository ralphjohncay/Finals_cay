import { apiRequest } from './client';
import { hydraMembers } from './hydra';
import type {
  Category,
  HydraCollection,
  Order,
  Product,
  Service,
} from './types';

export async function fetchProducts(token: string): Promise<Product[]> {
  const data = await apiRequest<HydraCollection<Product>>('/api/products', { token });
  return hydraMembers(data).filter((p) => p.isActive);
}

export async function fetchProduct(token: string, id: number): Promise<Product> {
  return apiRequest<Product>(`/api/products/${id}`, { token });
}

export async function fetchServices(token: string): Promise<Service[]> {
  const data = await apiRequest<HydraCollection<Service>>('/api/services', { token });
  return hydraMembers(data).filter((s) => s.isActive);
}

export async function fetchCategories(token: string): Promise<Category[]> {
  const data = await apiRequest<HydraCollection<Category>>('/api/categories', { token });
  return hydraMembers(data).filter((c) => c.isActive);
}

export async function fetchOrders(token: string): Promise<Order[]> {
  const data = await apiRequest<HydraCollection<Order>>('/api/orders', { token });
  return hydraMembers(data);
}

export async function createOrder(
  token: string,
  payload: {
    customer: string;
    status?: string;
    orderItems: Array<{
      name: string;
      price: string;
      quantity: number;
      type: 'product' | 'service';
      product?: string;
      service?: string;
    }>;
  },
): Promise<Order> {
  return apiRequest<Order>('/api/orders', {
    method: 'POST',
    token,
    body: {
      status: payload.status ?? 'pending_approval',
      customer: payload.customer,
      orderItems: payload.orderItems,
    },
  });
}

/** API Platform IRI for a user (required on order.customer). */
export function userIri(userId: number): string {
  return `/api/users/${userId}`;
}

export function productIri(productId: number): string {
  return `/api/products/${productId}`;
}

export function serviceIri(serviceId: number): string {
  return `/api/services/${serviceId}`;
}
