import React, { useEffect, useState } from 'react';
import { Alert, Image, ScrollView, StyleSheet, Text, View } from 'react-native';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { AppButton } from '../components/AppButton';
import { fetchProduct } from '../api/catalog';
import { useAuth } from '../context/AuthContext';
import { useCart } from '../context/CartContext';
import { assetUrl } from '../config';
import type { Product } from '../api/types';
import { colors, radius, spacing } from '../theme';
import type { RootStackParamList } from '../navigation';

type Props = NativeStackScreenProps<RootStackParamList, 'ProductDetail'>;

export function ProductDetailScreen({ route, navigation }: Props) {
  const { token } = useAuth();
  const { addProduct } = useCart();
  const [product, setProduct] = useState<Product | null>(null);

  useEffect(() => {
    if (!token) return;
    fetchProduct(token, route.params.id)
      .then(setProduct)
      .catch(() => Alert.alert('Error', 'Could not load product'));
  }, [token, route.params.id]);

  if (!product) {
    return <View style={styles.root} />;
  }

  const uri = assetUrl(product.image);

  return (
    <ScrollView style={styles.root} contentContainerStyle={styles.content}>
      {uri ? (
        <Image source={{ uri }} style={styles.image} />
      ) : (
        <View style={[styles.image, styles.placeholder]} />
      )}
      <Text style={styles.name}>{product.name}</Text>
      <Text style={styles.price}>${product.price}</Text>
      <Text style={styles.meta}>{product.category} · Stock {product.stock}</Text>
      {product.description ? <Text style={styles.desc}>{product.description}</Text> : null}

      <AppButton
        label="Add to cart"
        onPress={() => {
          addProduct(product);
          Alert.alert('Added', `${product.name} added to cart`, [
            { text: 'Continue' },
            { text: 'View cart', onPress: () => navigation.navigate('Cart') },
          ]);
        }}
        style={{ marginTop: spacing.lg }}
      />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.background },
  content: { padding: spacing.lg },
  image: { width: '100%', height: 220, borderRadius: radius.lg },
  placeholder: { backgroundColor: colors.backgroundAlt },
  name: { fontSize: 26, fontWeight: '700', color: colors.heading, marginTop: spacing.md },
  price: { fontSize: 22, fontWeight: '700', color: colors.primary, marginTop: 4 },
  meta: { color: colors.textMuted, marginTop: 4 },
  desc: { marginTop: spacing.md, color: colors.text, lineHeight: 22 },
});
