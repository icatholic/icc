Ext.define('icc.view.idatabase.Statistic.Chart', {
	extend: 'icc.common.Window',
	alias: 'widget.idatabaseStatisticChart',
	title: '统计结果',
	initComponent: function() {

		var statistics = this.__STATISTIC_INFO__;
		var extraParams = this.__EXTRAPARAMS__;
		var statistics_id = statistics.get('_id');
		var seriesType = statistics.get('seriesType');
		var type = {
			sum: '求和',
			avg: '均值',
			count: '计数',
			max: '最大值',
			min: '最小值',
			unique: '唯一值',
			median: '中位数',
			variance: '方差',
			standard: '标准差'
		};

		var store = new Ext.data.Store({
			fields: [{
				name: '_id',
				type: 'auto',
				convert: function(value, record) {
					if (Ext.isArray(value)) {
						return value.join('.');
					}
					return value;
				}
			}, {
				name: 'value',
				type: 'auto'
			}],
			autoLoad: false,
			proxy: {
				type: 'ajax',
				url: '/idatabase/data/statistic',
				timeout: 300000,
				extraParams: {
					action: 'statistic',
					__STATISTIC_ID__: statistics_id,
					__PROJECT_ID__: this.__PROJECT_ID__,
					__COLLECTION_ID__: this.__COLLECTION_ID__
				},
				reader: {
					type: 'json',
					root: 'result',
					totalProperty: 'total'
				}
			}
		});
		store.proxy.extraParams = Ext.Object.merge(store.proxy.extraParams, extraParams);

		if (seriesType !== 'pie') {
			var chart = Ext.create('Ext.chart.Chart', {
				style: 'background:#fff',
				store: store,
				title: statistics.get('name'),
				axes: [{
					type: 'Numeric',
					minimum: 0,
					position: 'left',
					fields: ['value'],
					title: statistics.get('yAxisTitle'),
					minorTickSteps: 1,
					grid: {
						odd: {
							opacity: 1,
							fill: '#ddd',
							stroke: '#bbb',
							'stroke-width': 0.5
						}
					}
				}, {
					type: 'Category',
					position: 'bottom',
					fields: ['_id'],
					title: statistics.get('xAxisTitle')
				}],
				series: [{
					type: statistics.get('seriesType'),
					axis: 'left',
					highlight: false,
					xField: '_id',
					yField: 'value',
					tips: {
						trackMouse: true,
						width: 'auto',
						height: 30,
						minHeight: 30,
						renderer: function(storeItem, item) {
							this.setTitle(storeItem.get('_id') + '的' + type[statistics.get('yAxisType')] + ':' + storeItem.get('value'));
						}
					}
				}]
			});
		} else {
			var chart = Ext.create('Ext.chart.Chart', {
				animate: true,
				store: store,
				shadow: true,
				legend: {
					position: 'right'
				},
				insetPadding: 60,
				theme: 'Base:gradients',
				series: [{
					type: 'pie',
					field: 'value',
					showInLegend: true,
					donut: true,
					tips: {
						trackMouse: true,
						width: 140,
						height: 28,
						renderer: function(storeItem, item) {
							var total = 0;
							store.each(function(rec) {
								total += rec.get('value');
							});
							this.setTitle(storeItem.get('_id') + ': ' + Math.round(storeItem.get('value') / total * 100, 2) + '%');
						}
					},
					highlight: {
						segment: {
							margin: 20
						}
					},
					label: {
						field: '_id',
						display: 'rotate',
						contrast: true,
						font: '18px Arial'
					}
				}]
			});
		}


		Ext.apply(this, {
			items: chart,
			dockedItems: [{
				xtype: 'toolbar',
				dock: 'top',
				items: [{
					xtype: 'button',
					text: '导出',
					iconCls: 'excel',
					action: 'statisticExport'
				}]
			}]
		});

		this.callParent();
	},
	listeners: {
		afterrender: function(win) {
			var mask = new Ext.LoadMask(win, {
				autoShow: true,
				msg: "统计中...",
				useMsg: true
			});

			var chart = win.down('chart');
			chart.store.load(function() {
				mask.hide();
				win.__BUTTON__.setDisabled(false);
			});

		}
	}
});