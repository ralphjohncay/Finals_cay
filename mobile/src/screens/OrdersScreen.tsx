import React, { useCallback, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  RefreshControl,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { ScreenHeader } from '../components/ScreenHeader';
import { fetchMyOrders } from '../api/catalog';
import { useAuth } from '../context/AuthContext';
import { useRefreshOnFocus } from '../hooks/useRefreshOnFocus';
import type { Order } from '../api/types';
import { colors, radius, spacing } from '../theme';

const STATUS_COLORS: Record<string, string> = {
  pending_approval: '#b08968',
  pending: '#e9c46a',
  approved: colors.success,
  completed: colors.primary,
  canceled: colors.error,
};

export function OrdersScreen() {
  const { token } = useAuth();
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async () => {
    if (!token) return;
    setOrders(await fetchMyOrders(token));
  }, [token]);

  useRefreshOnFocus(load);

  React.useEffect(() => {
    load()
      .catch(() => setOrders([]))
      .finally(() => setLoading(false));
  }, [load]);

  if (loading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator color={colors.primary} size="large" />
      </View>
    );
  }

  return (
    <View style={styles.root}>
      <ScreenHeader title="My Orders" subtitle="GET /api/orders/mine — shared Railway DB" />
      <FlatList
        data={orders}
        keyExtractor={(item) => String(item.id)}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={async () => {
              setRefreshing(true);
              await load().catch(() => undefined);
              setRefreshing(false);
            }}
          />
        }
        ListEmptyComponent={<Text style={styles.empty}>No orders yet. Checkout from the cart.</Text>}
        contentContainerStyle={{ gap: spacing.md, paddingBottom: spacing.xl }}
        renderItem={({ item }) => (
          <View style={styles.card}>
            <View style={styles.row}>
              <Text style={styles.id}>Order #{item.id}</Text>
              <Text
                style={[
                  styles.badge,
                  { backgroundColor: STATUS_COLORS[item.status] ?? colors.textMuted },
                ]}
              >
                {item.status.replace('_', ' ')}
              </Text>
            </View>
            <Text style={styles.meta}>
              {item.orderDate ? new Date(item.orderDate).toLocaleString() : '—'} · ${item.totalPrice}
            </Text>
          </View>
        )}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.background, padding: spacing.lg },
  centered: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  empty: { textAlign: 'center', color: colors.textMuted, marginTop: spacing.xl },
  card: {
    backgroundColor: colors.surface,
    borderRadius: radius.lg,
    padding: spacing.lg,
    borderWidth: 1,
    borderColor: colors.border,
  },
  row: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  id: { fontSize: 17, fontWeight: '700', color: colors.heading },
  badge: {
    color: '#fff',
    fontSize: 12,
    fontWeight: '700',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: radius.pill,
    textTransform: 'capitalize',
  },
  meta: { marginTop: 8, color: colors.textMuted },
});
