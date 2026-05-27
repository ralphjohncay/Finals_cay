export type HydraCollection<T> = {
  'hydra:member'?: T[];
  member?: T[];
  'hydra:totalItems'?: number;
  totalItems?: number;
};

export type Product = {
  id: number;
  name: string;
  price: string;
  description?: string | null;
  category?: string | null;
  stock: number;
  image?: string | null;
  isActive: boolean;
};

export type Service = {
  id: number;
  name: string;
  description: string;
  price: string;
  category: string;
  isActive: boolean;
};

export type Category = {
  id: number;
  name: string;
  description?: string | null;
  isActive: boolean;
};

export type OrderItem = {
  id?: number;
  name: string;
  price: string;
  quantity: number;
  type?: string | null;
  product?: string | null;
  service?: string | null;
};

export type Order = {
  id: number;
  orderDate: string;
  totalPrice: string;
  status: string;
  customer?: string;
  orderItems?: OrderItem[];
};

export type AuthUser = {
  email: string;
  name?: string;
  roles?: string[];
};

export type LoginResponse = {
  success?: boolean;
  token: string;
  user?: {
    id: number;
    email: string;
    name: string;
    roles: string[];
  };
};

export type RegisterResponse = {
  success: boolean;
  message: string;
  user?: { id: number; email: string; name: string; isVerified: boolean };
};

export type ApiError = {
  message?: string;
  detail?: string;
  code?: number;
};
