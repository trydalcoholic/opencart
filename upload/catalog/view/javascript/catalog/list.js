import { loader } from '../index.js';

export default class {
    async render() {
        let data = {};



        return this.render('catalog/product_info', { ...data, ...language, ...config });
    }
}