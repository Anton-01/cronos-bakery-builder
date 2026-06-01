import { defineStore } from 'pinia'

import { themeService } from '@/modules/cms/services/themeService'
import type { Menu, Theme } from '@/modules/cms/types'

interface ThemeState {
  theme: Theme | null
  headerMenu: Menu | null
  loaded: boolean
}

/** Build a Google Fonts stylesheet URL from the configured families. */
function googleFontsUrl(theme: Theme): string {
  if (theme.fonts.stylesheet) {
    return theme.fonts.stylesheet
  }
  const families = [theme.fonts.heading, theme.fonts.body]
    .filter(Boolean)
    .map((f) => `family=${encodeURIComponent(f)}:wght@400;600;700`)
    .join('&')
  return `https://fonts.googleapis.com/css2?${families}&display=swap`
}

function upsertLink(id: string, attrs: Record<string, string>): void {
  let link = document.getElementById(id) as HTMLLinkElement | null
  if (!link) {
    link = document.createElement('link')
    link.id = id
    document.head.appendChild(link)
  }
  Object.entries(attrs).forEach(([key, value]) => link!.setAttribute(key, value))
}

/**
 * Global theme store. Loads the active theme from the API and injects it into
 * the document at runtime — colours as CSS custom properties, Google Fonts and
 * the favicon — so branding changes require no redeploy.
 */
export const useThemeStore = defineStore('theme', {
  state: (): ThemeState => ({
    theme: null,
    headerMenu: null,
    loaded: false,
  }),

  getters: {
    colors: (state) => state.theme?.colors ?? null,
    footer: (state) => state.theme?.footer ?? null,
    logo: (state) => state.theme?.logo ?? null,
  },

  actions: {
    apply(theme: Theme): void {
      const root = document.documentElement

      // Corporate palette → CSS variables consumed across the UI.
      Object.entries(theme.colors).forEach(([name, value]) => {
        root.style.setProperty(`--color-${name}`, value)
      })
      root.style.setProperty('--font-heading', `'${theme.fonts.heading}', serif`)
      root.style.setProperty('--font-body', `'${theme.fonts.body}', sans-serif`)

      // Google Fonts + favicon.
      upsertLink('theme-fonts', { rel: 'stylesheet', href: googleFontsUrl(theme) })
      if (theme.favicon) {
        upsertLink('theme-favicon', { rel: 'icon', href: theme.favicon })
      }
    },

    async load(): Promise<void> {
      try {
        const [theme, menu] = await Promise.all([
          themeService.theme(),
          themeService.menu('header').catch(() => null),
        ])
        this.theme = theme
        this.headerMenu = menu
        if (theme) {
          this.apply(theme)
        }
      } finally {
        this.loaded = true
      }
    },
  },
})
