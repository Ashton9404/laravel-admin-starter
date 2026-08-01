import { watchEffect } from 'vue';

/**
 * Keeps the document title and meta description in step with the page.
 *
 * Honest about what this is: the app is a client-rendered SPA, so a crawler
 * that does not execute JavaScript sees the empty shell whatever this writes.
 * Search engines that do run JS pick it up, and it fixes the browser tab and
 * the bookmark title either way, which is reason enough. Real SEO for the
 * public site would need server-side rendering or prerendering — noted rather
 * than pretended otherwise.
 *
 * Takes getters so the values track the locale and the loaded record; a plain
 * string would freeze whatever was true on the first render.
 */
export function usePageMeta(getTitle, getDescription) {
    const siteName = 'Laravel Admin Starter';

    watchEffect(() => {
        const title = getTitle?.();
        document.title = title ? `${title} · ${siteName}` : siteName;

        const description = getDescription?.();

        if (description === undefined) {
            return;
        }

        let tag = document.querySelector('meta[name="description"]');

        if (!tag) {
            tag = document.createElement('meta');
            tag.setAttribute('name', 'description');
            document.head.appendChild(tag);
        }

        tag.setAttribute('content', description ?? '');
    });
}
