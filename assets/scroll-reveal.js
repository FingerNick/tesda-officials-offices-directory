if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
  const observer = new IntersectionObserver((entries) => {
    for (const entry of entries) {
      entry.target.classList.toggle('is-visible', entry.isIntersecting);
    }
  }, { threshold: 0.12 });

  const observeCards = () => {
    observer.disconnect();
    for (const card of document.querySelectorAll('.directory-card')) {
      card.classList.add('scroll-reveal');
      observer.observe(card);
    }
  };

  observeCards();
  document.addEventListener('directory:updated', observeCards);
}
