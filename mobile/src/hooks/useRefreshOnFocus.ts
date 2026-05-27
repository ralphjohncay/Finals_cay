import { useCallback } from 'react';
import { useFocusEffect } from '@react-navigation/native';

/** Refetch when the screen is opened or user navigates back to it. */
export function useRefreshOnFocus(refetch: () => void | Promise<void>): void {
  useFocusEffect(
    useCallback(() => {
      void refetch();
    }, [refetch]),
  );
}
