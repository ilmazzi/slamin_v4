import { apiGet, authApiDelete, authApiGet, authApiPatch, authApiPost } from './client.js'

const MAX_PER_PAGE = 3

function isUploadableFile(value) {
  if (typeof File !== 'undefined' && value instanceof File) {
    return true
  }

  if (typeof Blob !== 'undefined' && value instanceof Blob) {
    return true
  }

  return false
}

function buildPoemPayload(poem) {
  const image = poem?.image

  if (!isUploadableFile(image)) {
    return poem
  }

  const formData = new FormData()

  Object.entries(poem ?? {}).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '') {
      return
    }

    if (key === 'selectedGroups') {
      return
    }

    if (key === 'group_ids' && Array.isArray(value)) {
      value.forEach((id) => formData.append('group_ids[]', String(id)))
      return
    }

    if (isUploadableFile(value)) {
      formData.append(key, value)
      return
    }

    if (typeof value === 'boolean') {
      formData.append(key, value ? '1' : '0')
      return
    }

    formData.append(key, String(value))
  })

  return formData
}


export async function listPoems(
    { page = 1, perPage = 10, poemCategoryId = null, search = '', language = '', sort = 'newest', nickname = '' } = {},
    { signal } = {},
  ) {
    const params = new URLSearchParams()
    params.set('per_page', String(perPage))
    params.set('page', String(page))
    if (poemCategoryId) {
      params.set('poem_category_id', String(poemCategoryId))
    }
    if (search) {
      params.set('q', String(search))
    }
    if (language) {
      params.set('language', String(language))
    }
    if (sort) {
      params.set('sort', String(sort))
    }
    if (nickname) {
      params.set('nickname', String(nickname))
    }

    const { data, meta } = await apiGet(`/api/v1/poems?${params.toString()}`, { signal })
    const items = Array.isArray(data) ? data : []
    return { items, meta }
  }

export async function listFeaturedPoems({ page = 1, perPage = 3 } = {}, { signal } = {}) {
    const params = new URLSearchParams()
    params.set('per_page', String(perPage))
    params.set('page', String(page))

    const { data, meta } = await apiGet(`/api/v1/featured-poems?${params.toString()}`, { signal })
    const items = Array.isArray(data) ? data : []
    return { items, meta }
  }

export async function getPoemBySlug(slug, { token, signal } = {}) {
    const safeSlug = String(slug ?? '').trim()
    if (!safeSlug) {
      return null
    }

    const fetcher = token ? authApiGet : apiGet
    const { data } = await fetcher(`/api/v1/poems/${encodeURIComponent(safeSlug)}`, token
      ? {
          token,
          signal,
          fallbackMessage: 'Unable to load poem',
        }
      : {
          signal,
          fallbackMessage: 'Unable to load poem',
        })
    return data?.poem ?? null
  }

  export async function listPoemCategories({ signal } = {}) {
    const { data } = await apiGet('/api/v1/poem-categories', { signal })
    const categories = Array.isArray(data?.categories) ? data.categories : []
    return { categories }
  }

  export async function listPoemTypes({ signal } = {}) {
    const { data } = await apiGet('/api/v1/poem-types', { signal })
    const types = Array.isArray(data?.types) ? data.types : []
    return { types }
  }

  export async function createPoem(poem, { token, signal } = {}) {
    const payload = buildPoemPayload(poem)
    const { data } = await authApiPost('/api/v1/poems', payload, { token, signal })
    return data?.poem ?? null
  }

export async function listMyPoems(
  { page = 1, perPage = 50 } = {},
  { token, signal } = {},
) {
  const params = new URLSearchParams()
  params.set('per_page', String(perPage))
  params.set('page', String(page))

  const { data, meta } = await authApiGet(`/api/v1/my/poems?${params.toString()}`, {
    token,
    signal,
    fallbackMessage: 'Failed to fetch my poems',
  })

  return {
    items: Array.isArray(data) ? data : [],
    meta,
  }
}

export async function updatePoem(poemId, poem, { token, signal } = {}) {
  const payload = buildPoemPayload(poem)
  const path = `/api/v1/poems/${encodeURIComponent(poemId)}`
  const options = { token, signal, fallbackMessage: 'Unable to update poem' }
  const { data } = typeof FormData !== 'undefined' && payload instanceof FormData
    ? await authApiPost(path, appendMethodOverride(payload), options)
    : await authApiPatch(path, payload, options)
  return data?.poem ?? null
}

function appendMethodOverride(formData) {
  formData.append('_method', 'PATCH')
  return formData
}

export async function deletePoem(poemId, { token, signal } = {}) {
  await authApiDelete(`/api/v1/poems/${encodeURIComponent(poemId)}`, {
    token,
    signal,
    fallbackMessage: 'Unable to delete poem',
  })
}
