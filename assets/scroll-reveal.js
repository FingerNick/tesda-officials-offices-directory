if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
  const cards = document.querySelectorAll('.directory-card');
  const observer = new IntersectionObserver((entries) => {
    for (const entry of entries) {
      entry.target.classList.toggle('is-visible', entry.isIntersecting);
    }
  }, { threshold: 0.12 });

  for (const card of cards) {
    card.classList.add('scroll-reveal');
    observer.observe(card);
  }
}
