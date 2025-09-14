<?php

declare(strict_types=1);

namespace Opencart\System\Helper;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * @implements ArrayAccess<array-key, mixed>
 * @implements IteratorAggregate<array-key, mixed>
 */
final class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable {
	/**
	 * Initializes a new Collection instance with an optional array of items.
	 *
	 * @param array<array-key, mixed> $items initial items for the collection
	 */
	public function __construct(private array $items = []) {}

	/**
	 * @param array<array-key> $keys
	 *
	 * @return self
	 */
	public function only(array $keys): self {
		return new self(array_intersect_key($this->items, array_flip($keys)));
	}

	/**
	 * @param array<array-key> $keys
	 *
	 * @return self
	 */
	public function except(array $keys): self {
		return new self(array_diff_key($this->items, array_flip($keys)));
	}

	/**
	 * Adds an element by key and returns a new collection.
	 *
	 * @param mixed $key
	 * @param mixed $value
	 *
	 * @return self
	 */
	public function put(mixed $key, mixed $value): self {
		$items = $this->items;
		$items[$key] = $value;

		return new self($items);
	}

	/**
	 * Adds a value to the end of the collection and returns a new instance.
	 *
	 * @param mixed $value
	 *
	 * @return self
	 */
	public function push(mixed $value): self {
		$items = $this->items;
		$items[] = $value;

		return new self($items);
	}

	/**
	 * Merges additional items and returns a new collection.
	 *
	 * @param array<array-key, mixed> $items
	 *
	 * @return self
	 */
	public function merge(array $items): self {
		return new self(array_merge($this->items, $items));
	}

	/**
	 * Returns the value for the given key, or the default if the key does not exist.
	 *
	 * @param mixed $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function get(mixed $key, mixed $default = null): mixed {
		return $this->items[$key] ?? $default;
	}

	/**
	 * Checks if the given key exists in the collection.
	 *
	 * @param mixed $key
	 *
	 * @return bool
	 */
	public function has(mixed $key): bool {
		return isset($this->items[$key]);
	}

	/**
	 * @return array<array-key, mixed>
	 */
	public function all(): array {
		return $this->items;
	}

	/**
	 * @return array<array-key, mixed>
	 */
	public function toArray(): array {
		return $this->all();
	}

	/**
	 * Returns a query string prefixed with '&' if the collection is not empty.
	 *
	 * @return string
	 */
	public function toUrl(): string {
		if ($this->count() === 0) {
			return '';
		}

		return '&' . http_build_query($this->items);
	}

	/**
	 * Returns a plain query string without a prefix (for use with $this->url->link).
	 *
	 * @return string
	 */
	public function toQueryString(): string {
		return http_build_query($this->items);
	}

	/**
	 * Checks if the given offset exists in the collection.
	 *
	 * @param mixed $offset
	 *
	 * @return bool true if the offset exists, false otherwise
	 */
	public function offsetExists(mixed $offset): bool {
		return isset($this->items[$offset]);
	}

	/**
	 * Retrieves the value at the specified offset.
	 *
	 * @param mixed $offset
	 *
	 * @return mixed the value at the offset, or null if not set
	 */
	public function offsetGet(mixed $offset): mixed {
		return $this->items[$offset] ?? null;
	}

	/**
	 * Sets the value at the specified offset.
	 *
	 * @param mixed $offset
	 * @param mixed $value
	 *
	 * @return void
	 */
	public function offsetSet(mixed $offset, mixed $value): void {
		if ($offset === null) {
			$this->items[] = $value;
		} else {
			$this->items[$offset] = $value;
		}
	}

	/**
	 * Unsets the value at the specified offset.
	 *
	 * @param mixed $offset
	 *
	 * @return void
	 */
	public function offsetUnset(mixed $offset): void {
		unset($this->items[$offset]);
	}

	/**
	 * Returns an iterator to traverse the collection items.
	 *
	 * @return Traversable<int|string, mixed> an iterator for the collection
	 */
	public function getIterator(): Traversable {
		return new ArrayIterator($this->items);
	}

	/**
	 * Returns the number of items in the collection.
	 *
	 * @return int
	 */
	public function count(): int {
		return count($this->items);
	}

	/**
	 * @return array<array-key, mixed>
	 */
	public function jsonSerialize(): array {
		return $this->items;
	}
}
