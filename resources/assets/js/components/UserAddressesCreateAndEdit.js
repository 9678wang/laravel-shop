//注册一个名为user-addresses-create-and-edit的组件
Vue.component('user-addresses-create-and-edit',{
	//组件的数组
	data(){
		return{
			province:'',//省
			city:'',//市
			district:'',//区
		}
	},
	methods:{
		//把参数val中的值保存到数组的数据中
		onDistrictChanged(val){
			if(val.length === 3){
				this.province = val[0];
				this.city = val[1];
				this.district = val[2];
			}
		}
	}
});