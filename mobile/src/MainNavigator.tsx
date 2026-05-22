import React, { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { HomeScreen } from './screens/HomeScreen';
import { ShopScreen } from './screens/ShopScreen';
import { ServicesScreen } from './screens/ServicesScreen';
import { OrdersScreen } from './screens/OrdersScreen';
import { ProfileScreen } from './screens/ProfileScreen';
import { useCart } from './context/CartContext';
import { colors, spacing } from './theme';
import type { MainTabParamList, RootStackParamList } from './navigation';

const tabs: { key: keyof MainTabParamList; label: string }[] = [
  { key: 'Home', label: 'Home' },
  { key: 'Shop', label: 'Shop' },
  { key: 'Services', label: 'Services' },
  { key: 'Orders', label: 'Orders' },
  { key: 'Profile', label: 'Profile' },
];

export function MainNavigator() {
  const [active, setActive] = useState<keyof MainTabParamList>('Home');
  const { lines } = useCart();
  const navigation = useNavigation<NativeStackNavigationProp<RootStackParamList>>();

  return (
    <View style={styles.root}>
      <View style={styles.topBar}>
        <Text style={styles.brand}>RALPHS</Text>
        <Pressable onPress={() => navigation.navigate('Cart')} style={styles.cartBtn}>
          <Text style={styles.cartText}>Cart ({lines.length})</Text>
        </Pressable>
      </View>

      <View style={styles.content}>
        {active === 'Home' && <HomeScreen />}
        {active === 'Shop' && <ShopScreen />}
        {active === 'Services' && <ServicesScreen />}
        {active === 'Orders' && <OrdersScreen />}
        {active === 'Profile' && <ProfileScreen />}
      </View>

      <View style={styles.tabBar}>
        {tabs.map((tab) => (
          <Pressable
            key={tab.key}
            onPress={() => setActive(tab.key)}
            style={[styles.tab, active === tab.key && styles.tabActive]}
          >
            <Text style={[styles.tabLabel, active === tab.key && styles.tabLabelActive]}>
              {tab.label}
            </Text>
          </Pressable>
        ))}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.background },
  topBar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: colors.navbar,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  brand: { color: '#fff', fontWeight: '800', fontSize: 18, letterSpacing: 1 },
  cartBtn: {
    backgroundColor: colors.primary,
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 8,
  },
  cartText: { color: '#fff', fontWeight: '700' },
  content: { flex: 1 },
  tabBar: {
    flexDirection: 'row',
    borderTopWidth: 1,
    borderTopColor: colors.border,
    backgroundColor: colors.surface,
  },
  tab: { flex: 1, paddingVertical: 10, alignItems: 'center' },
  tabActive: { borderTopWidth: 2, borderTopColor: colors.primary },
  tabLabel: { fontSize: 11, color: colors.textMuted, fontWeight: '600' },
  tabLabelActive: { color: colors.primary },
});
