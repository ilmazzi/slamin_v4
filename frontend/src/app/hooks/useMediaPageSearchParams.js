import { useCallback } from 'react'
import { useSearchParams } from 'react-router-dom'

function parsePage(value) {
  return Math.max(1, Math.floor(Number.parseInt(value ?? '1', 10)) || 1)
}

function setOrDelete(params, key, value) {
  const normalized = String(value ?? '').trim()
  if (normalized) {
    params.set(key, normalized)
  } else {
    params.delete(key)
  }
}

export function useMediaPageSearchParams() {
  const [searchParams, setSearchParams] = useSearchParams()

  const search = searchParams.get('q') ?? ''
  const category = searchParams.get('category') ?? ''
  const sort = searchParams.get('sort') || 'newest'
  const videoPage = parsePage(searchParams.get('video_page'))
  const galleryPage = parsePage(searchParams.get('gallery_page'))

  const updateParams = useCallback((mutator, { resetPages = false } = {}) => {
    setSearchParams(
      (prev) => {
        const next = new URLSearchParams(prev)
        mutator(next)
        if (resetPages) {
          next.delete('video_page')
          next.delete('gallery_page')
        }
        return next
      },
      { replace: true },
    )
  }, [setSearchParams])

  const setSearch = useCallback((value) => {
    updateParams((next) => setOrDelete(next, 'q', value), { resetPages: true })
  }, [updateParams])

  const setCategory = useCallback((value) => {
    updateParams((next) => {
      const normalized = String(value ?? '').trim()
      if (!normalized || normalized === 'all') {
        next.delete('category')
      } else {
        next.set('category', normalized)
      }
    }, { resetPages: true })
  }, [updateParams])

  const setSort = useCallback((value) => {
    updateParams((next) => {
      const normalized = String(value ?? '').trim() || 'newest'
      if (normalized === 'newest') {
        next.delete('sort')
      } else {
        next.set('sort', normalized)
      }
    }, { resetPages: true })
  }, [updateParams])

  const setVideoPage = useCallback((page) => {
    updateParams((next) => {
      const nextPage = Math.max(1, Number(page) || 1)
      if (nextPage <= 1) {
        next.delete('video_page')
      } else {
        next.set('video_page', String(nextPage))
      }
    })
  }, [updateParams])

  const setGalleryPage = useCallback((page) => {
    updateParams((next) => {
      const nextPage = Math.max(1, Number(page) || 1)
      if (nextPage <= 1) {
        next.delete('gallery_page')
      } else {
        next.set('gallery_page', String(nextPage))
      }
    })
  }, [updateParams])

  return {
    search,
    category,
    sort,
    videoPage,
    galleryPage,
    setSearch,
    setCategory,
    setSort,
    setVideoPage,
    setGalleryPage,
  }
}
