import { Controller } from "@hotwired/stimulus";
import * as echarts from "echarts";

export default class extends Controller {
  connect() {
    const chartElement = document.getElementById("chart");
    const chartData = JSON.parse(chartElement.dataset.chartChartDataValue);
    const chartContainer = document.getElementById("chart-container");

    var myChart = echarts.init(chartContainer, "dark");

    // Construir el dataset
    const refrigerantsSet = new Set();
    const dataMap = {};

    chartData.forEach((entry) => {
      const categoryName = entry.name;
      dataMap[categoryName] = {};
      // !REGEX to eliminate human errors
      entry.data.forEach((item) => {
        const refrigerantName = item.name
          .trim()
          .toUpperCase()
          .replace(/[\s-]/g, "");
        refrigerantsSet.add(refrigerantName);
        dataMap[categoryName][refrigerantName] = item.valor;
      });
    });

    const refrigerants = Array.from(refrigerantsSet);
    const datasetSource = [["category", ...refrigerants]];

    Object.entries(dataMap).forEach(([category, values]) => {
      const row = [category];
      refrigerants.forEach((refrigerant) => {
        row.push(values[refrigerant] || 0);
      });
      datasetSource.push(row);
    });

    // Construir la opción del gráfico
    var option = {
      title: {
        text: "Refrigerantes por categoría",
      },
      legend: {},
      tooltip: {
        trigger: "axis",
        axisPointer: {
          type: "shadow",
        },
      },
      dataset: {
        source: datasetSource,
      },
      xAxis: {
        axisLabel: {
          fontWeight: "bold",
          distance: 15,
          rotate: 90,
          verticalAlign: "middle",
          fontSize: 14,
          position: "top",
          axisTick: { show: false },
          axisLine: { show: false },
          z: 2,
        },
        type: "category",
      },
      yAxis: {
        type: "value",
        max: "dataMax",
      },
      grid: {
        left: "3%",
        right: "4%",
        bottom: "3%",
        containLabel: true,
      },
      //   series: refrigerants.map(() => ({ type: "bar" })),
      series: refrigerants.map((refrigerant) => ({
        type: "bar",
        name: refrigerant,
        barGap: '0%',
        barCategoryGap: '10%',
        emphasis: {
          focus: "series",
        },
        encode: { x: "category", y: refrigerant },
      })),
    };

    option && myChart.setOption(option);
  }
}
