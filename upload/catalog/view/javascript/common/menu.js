import { loader } from '../index.js';

// Language
const language = await loader.language('common/menu');

// Storage
const categories = await loader.storage('catalog/category');

export default class {
    render() {
        let data = {};

        data.categories = categories;

        return loader.template('common/menu', { ...data,  ...language });
    }
}