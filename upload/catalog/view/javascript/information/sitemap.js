import { loader } from '../index.js';

// Language
const language = await loader.language('common/language');

export default class {
    render() {



        return loader.template('information/sitemap', language);
    }
}