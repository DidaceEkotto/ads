import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import fr from './fr.json';
import en from './en.json';

const messages = { fr, en };

export function useTranslation() {
  const page = usePage();
  const locale = computed(() => page.props.locale || 'fr');

  const t = (key) => {
    const dict = messages[locale.value] || messages.fr;
    return dict[key] ?? key;
  };

  return { t, locale };
}
