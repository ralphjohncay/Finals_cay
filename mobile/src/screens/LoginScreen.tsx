import React, { useState } from 'react';
import {
  Alert,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { AppButton } from '../components/AppButton';
import { useAuth } from '../context/AuthContext';
import { ApiClientError } from '../api/client';
import { colors, radius, spacing, typography } from '../theme';
import type { RootStackParamList } from '../navigation';

type Props = NativeStackScreenProps<RootStackParamList, 'Login'>;

export function LoginScreen({ navigation }: Props) {
  const { signIn } = useAuth();
  const [email, setEmail] = useState('customer@shoes.com');
  const [password, setPassword] = useState('customer123');
  const [loading, setLoading] = useState(false);

  const onSubmit = async () => {
    setLoading(true);
    try {
      await signIn(email, password);
    } catch (e) {
      const msg =
        e instanceof ApiClientError
          ? e.message
          : e instanceof Error
            ? e.message
            : 'Login failed';
      Alert.alert('Sign in', msg);
    } finally {
      setLoading(false);
    }
  };

  return (
    <KeyboardAvoidingView
      style={styles.root}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <ScrollView contentContainerStyle={styles.scroll}>
        <View style={styles.hero}>
          <Text style={styles.brand}>RALPHS Footwear</Text>
          <Text style={styles.tagline}>Quality shoes & services</Text>
        </View>

        <View style={styles.card}>
          <Text style={styles.title}>Sign in</Text>
          <Text style={typography.subtitle}>Use the same account as the website</Text>

          <Text style={styles.label}>Email</Text>
          <TextInput
            value={email}
            onChangeText={setEmail}
            autoCapitalize="none"
            keyboardType="email-address"
            style={styles.input}
            placeholder="you@email.com"
            placeholderTextColor={colors.textMuted}
          />

          <Text style={styles.label}>Password</Text>
          <TextInput
            value={password}
            onChangeText={setPassword}
            secureTextEntry
            style={styles.input}
            placeholder="Password"
            placeholderTextColor={colors.textMuted}
          />

          <AppButton label="Login" onPress={onSubmit} loading={loading} style={{ marginTop: spacing.md }} />
          <AppButton
            label="Create account"
            variant="outline"
            onPress={() => navigation.navigate('Register')}
            style={{ marginTop: spacing.sm }}
          />
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.background },
  scroll: { flexGrow: 1, padding: spacing.lg, justifyContent: 'center' },
  hero: {
    backgroundColor: colors.primary,
    borderRadius: radius.lg,
    padding: spacing.lg,
    marginBottom: spacing.lg,
  },
  brand: { color: '#fff', fontSize: 26, fontWeight: '800' },
  tagline: { color: 'rgba(255,255,255,0.9)', marginTop: 6, fontSize: 15 },
  card: {
    backgroundColor: colors.surface,
    borderRadius: radius.lg,
    padding: spacing.lg,
    borderWidth: 1,
    borderColor: colors.border,
  },
  title: { fontSize: 22, fontWeight: '700', color: colors.heading, marginBottom: 4 },
  label: { ...typography.label, marginTop: spacing.md, marginBottom: 6 },
  input: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.md,
    paddingHorizontal: spacing.md,
    paddingVertical: 12,
    fontSize: 16,
    color: colors.text,
    backgroundColor: '#fff',
  },
});
