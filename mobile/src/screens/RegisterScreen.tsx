import React, { useState } from 'react';
import { Alert, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { AppButton } from '../components/AppButton';
import { useAuth } from '../context/AuthContext';
import { ApiClientError } from '../api/client';
import { colors, radius, spacing, typography } from '../theme';
import type { RootStackParamList } from '../navigation';

type Props = NativeStackScreenProps<RootStackParamList, 'Register'>;

export function RegisterScreen({ navigation }: Props) {
  const { signUp } = useAuth();
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);

  const onSubmit = async () => {
    setLoading(true);
    try {
      const message = await signUp(email, name, password);
      Alert.alert('Account created', message, [
        { text: 'OK', onPress: () => navigation.navigate('Login') },
      ]);
    } catch (e) {
      const msg = e instanceof ApiClientError ? e.message : 'Registration failed';
      Alert.alert('Register', msg);
    } finally {
      setLoading(false);
    }
  };

  return (
    <ScrollView style={styles.root} contentContainerStyle={styles.content}>
      <Text style={styles.title}>Create account</Text>
      <Text style={typography.subtitle}>Matches /api/register on the Symfony backend</Text>

      <Text style={styles.label}>Name</Text>
      <TextInput value={name} onChangeText={setName} style={styles.input} />

      <Text style={styles.label}>Email</Text>
      <TextInput
        value={email}
        onChangeText={setEmail}
        autoCapitalize="none"
        keyboardType="email-address"
        style={styles.input}
      />

      <Text style={styles.label}>Password</Text>
      <TextInput value={password} onChangeText={setPassword} secureTextEntry style={styles.input} />

      <AppButton label="Sign up" onPress={onSubmit} loading={loading} style={{ marginTop: spacing.lg }} />
      <AppButton label="Back to login" variant="ghost" onPress={() => navigation.goBack()} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.background },
  content: { padding: spacing.lg },
  title: { fontSize: 24, fontWeight: '700', color: colors.heading },
  label: { ...typography.label, marginTop: spacing.md, marginBottom: 6 },
  input: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.md,
    padding: 12,
    backgroundColor: '#fff',
    fontSize: 16,
  },
});
