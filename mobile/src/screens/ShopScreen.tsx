import React, { useCallback, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  RefreshControl,
  StyleSheet,
  View,
} from 'react-native';
import { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { useNavigation } from '@react-navigation/native';
import { ProductCard } from '../components/ProductCard';
import { ScreenHeader } from '../components/ScreenHeader';
import { fetchProducts } from '../api/catalog';
import { useAuth } from '../context/AuthContext';
import { useRefreshOnFocus } from '../hooks/useRefreshOnFocus';
import type { Product } from '../api/types';
import { colors, spacing } from '../theme';
import type { RootStackParamList } from '../navigation';

export function ShopScreen() {
  const { token } = useAuth();
  const navigation = useNavigation<NativeStackNavigationProp<RootStackParamList>>();
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async () => {
    const list = await fetchProducts(token);
    setProducts(list);
  }, [token]);

  useRefreshOnFocus(load);

  React.useEffect(() => {
    load()
      .catch(() => setProducts([]))
      .finally(() => setLoading(false));
  }, [load]);

  const onRefresh = async () => {
    setRefreshing(true);
    await load().catch(() => undefined);
    setRefreshing(false);
  };

  if (loading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  return (
    <View style={styles.root}>
      <ScreenHeader
        title="Shop Products"
        subtitle="Synced with Railway — pull to refresh for latest"
      />
      <FlatList
        data={products}
        keyExtractor={(item) => String(item.id)}
        numColumns={2}
        columnWrapperStyle={styles.row}
        contentContainerStyle={styles.list}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
        renderItem={({ item }) => (
          <ProductCard
            product={item}
            onPress={() => navigation.navigate('ProductDetail', { id: item.id })}
          />
        )}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.background, paddingHorizontal: spacing.lg },
  centered: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  list: { paddingBottom: spacing.xl, gap: spacing.md },
  row: { gap: spacing.md, justifyContent: 'space-between' },
});
