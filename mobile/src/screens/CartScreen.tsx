import React, { useState } from 'react';
import { Alert, FlatList, StyleSheet, Text, View } from 'react-native';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { AppButton } from '../components/AppButton';
import { ScreenHeader } from '../components/ScreenHeader';
import { createOrder } from '../api/catalog';
import { useAuth } from '../context/AuthContext';
import { lineKey, useCart } from '../context/CartContext';
import { ApiClientError } from '../api/client';
import { colors, radius, spacing } from '../theme';
import type { RootStackParamList } from '../navigation';

type Props = NativeStackScreenProps<RootStackParamList, 'Cart'>;

export function CartScreen({ navigation }: Props) {
  const { token } = useAuth();
  const { lines, total, toOrderItems, clear, removeLine } = useCart();
  const [submitting, setSubmitting] = useState(false);

  const checkout = async () => {
    if (!token || lines.length === 0) return;
    setSubmitting(true);
    try {
      await createOrder(token, {
        orderItems: toOrderItems(),
      });
      clear();
      Alert.alert('Order placed', 'Your order is pending approval.', [
        { text: 'OK', onPress: () => navigation.goBack() },
      ]);
    } catch (e) {
      Alert.alert('Checkout', e instanceof ApiClientError ? e.message : 'Failed to place order');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <View style={styles.root}>
      <ScreenHeader title="Cart" subtitle={`Total: $${total.toFixed(2)}`} />
      <FlatList
        data={lines}
        keyExtractor={(line) => lineKey(line)}
        ListEmptyComponent={<Text style={styles.empty}>Your cart is empty.</Text>}
        contentContainerStyle={{ gap: spacing.sm, paddingBottom: 120 }}
        renderItem={({ item }) => {
          const name = item.kind === 'product' ? item.product.name : item.service.name;
          const price = item.kind === 'product' ? item.product.price : item.service.price;
          return (
            <View style={styles.row}>
              <View style={{ flex: 1 }}>
                <Text style={styles.name}>{name}</Text>
                <Text style={styles.meta}>
                  ${price} × {item.quantity}
                </Text>
              </View>
              <AppButton
                label="Remove"
                variant="ghost"
                onPress={() => removeLine(lineKey(item))}
              />
            </View>
          );
        }}
      />
      <View style={styles.footer}>
        <AppButton
          label="Place order"
          onPress={checkout}
          loading={submitting}
          disabled={lines.length === 0}
        />
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.background, padding: spacing.lg },
  empty: { textAlign: 'center', color: colors.textMuted, marginTop: 40 },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderRadius: radius.md,
    padding: spacing.md,
    borderWidth: 1,
    borderColor: colors.border,
  },
  name: { fontWeight: '700', color: colors.heading },
  meta: { color: colors.textMuted, marginTop: 2 },
  footer: {
    position: 'absolute',
    left: spacing.lg,
    right: spacing.lg,
    bottom: spacing.lg,
  },
});
