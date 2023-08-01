import "../node_modules/latte-carousel/dist/latte-carousel.min.css";
import { Carousel } from "../node_modules/latte-carousel/dist/latte-carousel.min.js";

var options = {
    count: 5,
    move: 1,
    touch: true,
    mode: "align",
    buttons: true,
    dots: true,
    rewind: true,
    autoplay: 0,
    animation: 500,
    responsive: {
        "0": { count: 1.5, mode: "free", buttons: false },
        "480": { count: 2.5, mode: "free", buttons: false },
        "768": { count: 3, move: 3, touch: false, dots: true },
        "1440": { count: 6, move: 2, touch: false, dots: true },
    },
};

if (document.getElementById('carousel') !== null) {
    new Carousel("#carousel", options);
}

