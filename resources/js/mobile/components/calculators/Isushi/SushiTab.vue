<template>
    <div>
        <h4 class="text-center mt-2">Собираем вкусный ролл</h4>
        <h6 class="text-left mt-2 mb-2">
            <mark>Выбираем покрытие ролла</mark>
        </h6>
        <div class="row mt-2 mb-2">
            <div class="col-12 col-sm-12 col-md-6" v-for="fill in getFilling(11)">
                <div class="container-wrapper">
                    <label class="container">{{fill.title}}<span
                        class="badge badge-weight">{{fill.weight}} гр.</span><span
                        class="badge">{{fill.price | currency}}</span>


                        <input v-if="fill.checked" checked="checked" type="checkbox"
                               :disabled="fill.disabled||hasManyItems(fill.id)>1" v-model="fillings" :value="fill.id">
                        <input v-else type="checkbox"
                               :disabled="fill.disabled||hasManyItems(fill.id)>1"
                               v-model="pizza_base" :value="fill.id">
                        <span class="checkmark"></span>


                    </label>

                </div>

            </div>
        </div>

        <div class="row mt-2 mb-2">
            <div class="col-12 col-sm-12 col-md-6" v-for="fill in getFilling(9)">
                <div class="container-wrapper">
                    <label class="container">{{fill.title}}<span
                        class="badge badge-weight">{{fill.weight}} гр.</span><span
                        class="badge">{{fill.price | currency}}</span>


                        <input v-if="fill.checked" checked="checked" type="radio" name="roll_coating"
                               :disabled="fill.disabled||hasManyItems(fill.id)>1" v-model="roll_coating"
                               :value="fill.id">
                        <input v-else type="radio" name="roll_coating"
                               :disabled="fill.disabled||hasManyItems(fill.id)>1"
                               v-model="roll_coating" :value="fill.id">
                        <span class="checkmark"></span>


                    </label>

                </div>

            </div>
        </div>
        <h6 class="text-center">
            <mark>А теперь выбираем начинку ролла...</mark>
        </h6>

        <div class="row mt-2 mb-2">
            <div class="col-12 col-sm-12 col-md-6" v-for="fill in getFilling(10)">

                <div class="container-wrapper">
                    <label class="container">{{fill.title}}<span
                        class="badge badge-weight">{{fill.weight}} гр.</span><span
                        class="badge">{{fill.price | currency}}</span>
                        <input v-if="fill.checked" checked="checked" type="checkbox" name="filling"
                               :disabled="fill.disabled||hasManyItems(fill.id)>1||(fillings.length >= 6&&fillings.indexOf(fill.id) === -1)" v-model="fillings" :value="fill.id">
                        <input v-else type="checkbox" name="filling" :value="fill.id"
                               :disabled="fill.disabled||hasManyItems(fill.id)>1||(fillings.length >= 6&&fillings.indexOf(fill.id) === -1)"
                               v-model="fillings">
                        <span class="checkmark"></span>
                    </label>

                  <!--  <div v-if="!fill.disabled">
                        <div class="counter-wrapper" v-if="hasManyItems(fill.id)>0">
                            <div class="counter">
                                <button class="btn btn-counter" @click="removeItem(fill.id)">-</button>
                                <p>{{hasManyItems(fill.id)}}</p>
                                <button class="btn btn-counter" @click="addItem(fill.id)">+</button>
                            </div>
                        </div>
                    </div>-->
                </div>


            </div>

        </div>


        <hr>
        <p class="text-center"><em> <strong>Цена указана за 1 порцию роллов (вы заказали
           <mark> {{summary_count}}</mark>
            порций). Порция включает в себя 8 штук роллов общей массой <mark>{{weight}}</mark>
            грамм.</strong></em></p>
        <div class="row d-flex justify-content-center result">
            <div class="col-12 col-sm-12 col-md-6 col-lg-4">
                <h6 class="text-center">Сколько таких роллов сделать?</h6>
                <div class="summary">
                    <div class="btn-counter" @click="decrementSummary">-</div>
                    <p>{{summary_count}}</p>
                    <div class="btn-counter" @click="incrementSummary">+</div>
                </div>
                <hr>

                <div class="d-flex justify-content-center">
                    <ul>
                        <li>Вес: <strong>{{summary_weight}} грамм</strong></li>
                        <li>Цена: <strong>{{summary_price}} руб</strong></li>
                    </ul>
                </div>

                <p class="text-center" v-if="message.length>0">
                    <mark class="text-white">{{message}}</mark>
                </p>
                <div class="d-flex justify-content-center flex-wrap">

                    <div class="col-12 col-sm-6 col-md-6 d-flex justify-content-center">
                        <button class="btn btn-primary w-100 mt-2" :disabled="summary_count===0" @click="addToCart">
                            В корзину
                        </button>
                    </div>

                    <div class="col-12 col-sm-6 col-md-6 d-flex justify-content-center">
                        <button class="btn  btn-danger w-100 mt-2" @click="clearCalc">
                            Очистить
                        </button>
                    </div>

                </div>


            </div>
        </div>

    </div>
</template>
<script>
    export default {
        data() {
            return {
                timer: null,
                message: '',
                roll_coating: 77,
                summary_count: 0,
                summary_weight: 0,
                summary_price: 0,
                price: 0,
                weight: 0,
                fillings: []
            }
        },
        watch: {
            roll_coating: function (newVal, oldVal) {
                if (oldVal)
                    this.removeItem(oldVal)
                if (newVal)
                    this.addItem(newVal)
            },
            summary_count: function (val) {
                this.summary_price = this.price * this.summary_count
                this.summary_weight = this.weight * this.summary_count
            },
            fillings: function (val) {
                console.log(this.fillings.length)
                window
                    .api
                    .watchForFillings(this, val)


            }
            ,
            products: function (newVal, oldVal) {
                return newVal
            },
        },
        computed: {
            products() {
                return this.$store.getters.cartProducts;
            }
        },
        mounted() {
            window
                .api
                .prepareCheckedItems(this, [9, 10, 11])
                .loadRestInfo(this, "isushi")

            let callback = (val, oldVal, uri) => {
                this.$store.dispatch("getProductList")
            }

            Vue.ls.on('store', callback) //watch change foo key and triggered callbac
        },
        methods: {
            addToCart() {
                window
                    .api
                    .addToCart(this,"Собранный ролл")
            },
            clearCalc() {
                window
                    .api
                    .clearCalc(this)

                window
                    .api
                    .prepareCheckedItems(this, [9, 10, 11])
            },
            comingSoon() {
                this.message = "Данный сервис будет доступен в ближайшее время!"
                this.sendMessage(
                    "Сервис еще недоступен!"
                )
            },
            decrementSummary() {
                window
                    .api
                    .decrementSummary(this)
            },
            incrementSummary() {
                window
                    .api
                    .incrementSummary(this)
            },
            hasManyItems(id) {
                return window
                    .api
                    .hasManyItems(this, id)
            },
            removeItem(id) {
                window
                    .api
                    .removeItem(this, id)
            },
            addItem(id) {
                window
                    .api
                    .addItem(this, id)
            },
            getCategory(id) {
                return window
                    .api
                    .getCategory(this, id)
            },
            getFilling(type) {
                return window
                    .api
                    .getFilling(this, type)

            },
            sendMessage(message) {
                this.$notify({
                    group: 'info',
                    type: 'success',
                    title: 'Калькулятор вкусняшек Fastoran',
                    text: message
                });
            },



        }
    }
</script>
<style lang="scss" scoped>
    hr {
        width: 100%;
        height: 1px;
        /* background: darkred; */
        border-top: 1px darkred solid;
        margin-top: 5px;
        margin-bottom: 5px;
        padding: 10px;
        box-sizing: border-box;
    }


    h6,
    h2,
    h3 {
        mark {
            background: transparent;
            border-bottom:2px #d50c0d solid;
            //color: white;
        }
    }

    .container-wrapper {
        display: flex;
        justify-content: space-between;
    }

    .result {
        ul {
            width: 150px;

            li {
                width: 100%;
                display: flex;
                justify-content: space-between;

            }
        }

        .summary {
            width: 100%;
            display: flex;
            padding: 10px;
            justify-content: space-around;
            align-items: center;

            p {
                padding: 10px;
            }

            .btn-counter {
                width: 50px;
                height: 50px;
                display: flex;
                justify-content: center;
                align-items: center;
                background: darkorange;
                font-size: 20px;
                font-weight: bolder;
                color: #4e3d03;
            }
        }
    }

    .counter-wrapper {
        display: inline-block;

        .counter {
            display: flex;
            justify-content: space-between;
            width: 100px;
            padding: 5px;

            .btn-counter {
                width: 25px;
                height: 25px;
                background: darkorange;
                color: white;
                padding: 0px;
                display: flex;
                justify-content: center;
                align-items: center;
            }
        }
    }
</style>
