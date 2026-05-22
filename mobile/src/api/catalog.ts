import { apiRequest } from './client';
import { hydraMembers } from './hydra';
import type {
  Category,
  HydraCollection,
  Order,
  Product,
  Service,
} from './types';

export async function fetchProducts(token?: string | null): Promise<Product[]> {
  const data = await apiRequest<HydraCollection<Product>>('/api/products', { token });
  return hydraMembers(data).filter((p) => p.isActive);
}

export async function fetchProduct(id: number, token?: string | null): Promise<Product> {
  return apiRequest<Product>(`/api/products/${id}`, { token });
}

export async function fetchServices(token?: string | null): Promise<Service[]> {
  const data = await apiRequest<HydraCollection<Service>>('/api/services', { token });
  return hydraMembers(data).filter((s) => s.isActive);
}

export async function fetchCategories(token?: string | null): Promise<Category[]> {
  const data = await apiRequest<HydraCollection<Category>>('/api/categories', { token });
  return hydraMembers(data).filter((c) => c.isActive);
}

/** Current user's orders (same MySQL rows as website admin). */
export async function fetchMyOrders(token: string): Promise<Order[]> {
  const data = await apiRequest<{ orders: Order[] }>('/api/orders/mine', { token });
  return hydraMembers(data);
}

export async function fetchOrdersForUser(token: string, userId: number): Promise<Order[]> {
  const data = await apiRequest<{ orders: Order[] }>(`/api/orders/user/${userId}`, { token });
  return hydraMembers(data);
}

export type CheckoutLine = {
  name: string;
  price: string;
  quantity: number;
  type: 'product' | 'service';
  product?: number | string;
  service?: number | string;
};

export async function checkoutOrder(
  token: string,
  orderItems: CheckoutLine[],
  status = 'pending_approval',
): Promise<{ id: number; status: string; totalPrice: string; message: string }> {
  return apiRequest('/api/orders/checkout', {
    method: 'POST',
    token,
    body: { orderItems, status },
  });
}

/** Place order via POST /api/orders (preferred; same DB as website). */
export async function createOrder(
  token: string,
  body: {
    items?: Array<{ productId: number; quantity: number } | { serviceId: number; quantity: number }>;
    orderItems?: CheckoutLine[];
    status?: string;
    deliveryAddress?: string;
    paymentMethod?: string;
  },
): Promise<{ success: boolean; message: string; order: Order }> {
  return apiRequest('/api/orders', {
    method: 'POST',
    token,
    body,
  });
}

export function productRef(productId: number): string {
  return `/api/products/${productId}`;
}

export function serviceRef(serviceId: number): string {
  return `/api/services/${serviceId}`;
}

export const productIri = productRef;
export const serviceIri = serviceRef;

export function userIri(userId: number): string {
  return `/api/users/${userId}`;
}
