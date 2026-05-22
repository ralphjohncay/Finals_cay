import React, { createContext, useCallback, useContext, useMemo, useState } from 'react';
import type { Product, Service } from '../api/types';
import { productIri, serviceIri } from '../api/catalog';

export type CartLine =
  | { kind: 'product'; product: Product; quantity: number }
  | { kind: 'service'; service: Service; quantity: number };

type CartContextValue = {
  lines: CartLine[];
  addProduct: (product: Product, qty?: number) => void;
  addService: (service: Service, qty?: number) => void;
  updateQuantity: (key: string, quantity: number) => void;
  removeLine: (key: string) => void;
  clear: () => void;
  total: number;
  toOrderItems: () => Array<{
    name: string;
    price: string;
    quantity: number;
    type: 'product' | 'service';
    product?: string;
    service?: string;
  }>;
};

const CartContext = createContext<CartContextValue | undefined>(undefined);

function lineKey(line: CartLine): string {
  return line.kind === 'product' ? `p-${line.product.id}` : `s-${line.service.id}`;
}

export function CartProvider({ children }: { children: React.ReactNode }) {
  const [lines, setLines] = useState<CartLine[]>([]);

  const addProduct = useCallback((product: Product, qty = 1) => {
    setLines((prev) => {
      const key = `p-${product.id}`;
      const idx = prev.findIndex((l) => lineKey(l) === key);
      if (idx >= 0) {
        const copy = [...prev];
        const line = copy[idx];
        if (line.kind === 'product') {
          copy[idx] = { ...line, quantity: line.quantity + qty };
        }
        return copy;
      }
      return [...prev, { kind: 'product', product, quantity: qty }];
    });
  }, []);

  const addService = useCallback((service: Service, qty = 1) => {
    setLines((prev) => {
      const key = `s-${service.id}`;
      const idx = prev.findIndex((l) => lineKey(l) === key);
      if (idx >= 0) {
        const copy = [...prev];
        const line = copy[idx];
        if (line.kind === 'service') {
          copy[idx] = { ...line, quantity: line.quantity + qty };
        }
        return copy;
      }
      return [...prev, { kind: 'service', service, quantity: qty }];
    });
  }, []);

  const updateQuantity = useCallback((key: string, quantity: number) => {
    if (quantity <= 0) {
      setLines((prev) => prev.filter((l) => lineKey(l) !== key));
      return;
    }
    setLines((prev) =>
      prev.map((l) => (lineKey(l) === key ? { ...l, quantity } : l)),
    );
  }, []);

  const removeLine = useCallback((key: string) => {
    setLines((prev) => prev.filter((l) => lineKey(l) !== key));
  }, []);

  const clear = useCallback(() => setLines([]), []);

  const total = useMemo(
    () =>
      lines.reduce((sum, line) => {
        const price =
          line.kind === 'product'
            ? parseFloat(line.product.price)
            : parseFloat(line.service.price);
        return sum + price * line.quantity;
      }, 0),
    [lines],
  );

  const toOrderItems = useCallback(() => {
    return lines.map((line) => {
      if (line.kind === 'product') {
        return {
          name: line.product.name,
          price: line.product.price,
          quantity: line.quantity,
          type: 'product' as const,
          product: productIri(line.product.id),
        };
      }
      return {
        name: line.service.name,
        price: line.service.price,
        quantity: line.quantity,
        type: 'service' as const,
        service: serviceIri(line.service.id),
      };
    });
  }, [lines]);

  const value = useMemo(
    () => ({
      lines,
      addProduct,
      addService,
      updateQuantity,
      removeLine,
      clear,
      total,
      toOrderItems,
    }),
    [lines, addProduct, addService, updateQuantity, removeLine, clear, total, toOrderItems],
  );

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
}

export function useCart(): CartContextValue {
  const ctx = useContext(CartContext);
  if (!ctx) throw new Error('useCart requires CartProvider');
  return ctx;
}

export { lineKey };
