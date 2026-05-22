import React from 'react';
import { Image, Pressable, StyleSheet, Text, View } from 'react-native';
import { assetUrl } from '../config';
import type { Product } from '../api/types';
import { colors, radius, spacing } from '../theme';

type Props = {
  product: Product;
  onPress: () => void;
};

export function ProductCard({ product, onPress }: Props) {
  const uri = assetUrl(product.image);
  return (
    <Pressable onPress={onPress} style={({ pressed }) => [styles.card, pressed && styles.pressed]}>
      {uri ? (
        <Image source={{ uri }} style={styles.image} resizeMode="cover" />
      ) : (
        <View style={[styles.image, styles.placeholder]}>
          <Text style={styles.placeholderText}>RALPHS</Text>
        </View>
      )}
      <View style={styles.body}>
        <Text style={styles.name} numberOfLines={1}>
          {product.name}
        </Text>
        <Text style={styles.meta}>{product.category ?? 'General'}</Text>
        <Text style={styles.price}>${product.price}</Text>
        <Text style={styles.stock}>Stock: {product.stock}</Text>
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.surface,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.border,
    overflow: 'hidden',
    flex: 1,
    minWidth: 160,
    maxWidth: '48%',
  },
  pressed: { opacity: 0.92 },
  image: { width: '100%', height: 120 },
  placeholder: {
    backgroundColor: colors.backgroundAlt,
    alignItems: 'center',
    justifyContent: 'center',
  },
  placeholderText: { color: colors.primary, fontWeight: '800', letterSpacing: 1 },
  body: { padding: spacing.md, gap: 4 },
  name: { fontSize: 16, fontWeight: '700', color: colors.heading },
  meta: { fontSize: 13, color: colors.textMuted },
  price: { fontSize: 18, fontWeight: '700', color: colors.primary, marginTop: 4 },
  stock: { fontSize: 12, color: colors.textMuted },
});
