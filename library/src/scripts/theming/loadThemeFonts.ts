/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { getDeferredStoreState } from "@library/redux/getStore";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import WebFont from "webfontloader";

const defaultFontConfig: WebFont.Config = {
    custom: {
        families: ["Open Sans"],
        urls: ["https://fonts.googleapis.com/css?family=Open+Sans"],
    },
};

export function loadThemeFonts() {
    const state = getDeferredStoreState<ICoreStoreState, null>(null);
    if (state !== null) {
        const assets = state.theme.assets.data || {};
        const { fonts } = assets;

        if (fonts) {
            const webFontConfig: WebFont.Config = {
                custom: {
                    families: fonts.map(font => font.name),
                    urls: fonts.map(font => font.url),
                },
            };

            if (webFontConfig.custom && webFontConfig.custom.urls && webFontConfig.custom.urls.length > 0) {
                WebFont.load(webFontConfig);
            } else {
                // Default font loading
                WebFont.load(defaultFontConfig);
            }
        }
    }
}
