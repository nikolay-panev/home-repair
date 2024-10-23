<template>
    <div class="wrapper">
        <div class="loader"></div>
    </div>
</template>

<style scoped>
.wrapper {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.95);
    width: 100%;
    height: 100%;
    z-index: 10;
}
.loader {
    width: 150px;
    --b: 28px;
    position:fixed;
    left:calc(50% - 75px);
    top:calc(50% - 75px);
    aspect-ratio: 1;
    border-radius: 50%;
    padding: 1px;
    background: conic-gradient(#0000 10%,#0084FF) content-box;
    -webkit-mask: repeating-conic-gradient(#0000 0deg,#000 1deg 20deg,#0000 21deg 36deg), radial-gradient(farthest-side,#0000 calc(100% - var(--b) - 1px),#000 calc(100% - var(--b)));
    -webkit-mask-composite: destination-in;
    mask: repeating-conic-gradient(#0000 0deg,#000 1deg 20deg,#0000 21deg 36deg), radial-gradient(farthest-side,#0000 calc(100% - var(--b) - 1px),#000 calc(100% - var(--b)));
    mask-composite: intersect;
    animation: rotate 1s infinite steps(10);
}
@keyframes rotate {
    to{transform: rotate(1turn)}
}
</style>