import { loader } from '../index.js';

// Config
const config = await loader.config('default');

// Language
const language = await loader.language('common/header');

// library
const session = await loader.library('session');

export default class {
    render() {
        let data = {};

        data.wishlist = 0;

        data.logged = session.has('customer');

        if (data.logged) {
            data.wishlist = session.get('customer').getWishlist().length;
        }

        return loader.template('common/header', { ...data, ...language, ...config });
    }

    register(e) {
        e.preventDefault();

        console.log('register');

        console.log(this.$contact);
    }

    login(e) {
        e.preventDefault();

        console.log('login');
    }
}